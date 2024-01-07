<?php

/**
 * Utility Functions!
 * This PHP file contains a collection of useful functions for various tasks.
 * Feel free to use this file in your projects, but please be aware that it comes with no warranties or guarantees. You are responsible for testing and using these functions at your own risk.
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 14
 */
class R
{
    /**
     * Empty string that equals '';
     */
    public const EMPTY = '';

    /**
     * Space string that equals ' ';
     */
    public const SPACE = ' ';

    /**
     * Primitive event system.
     * @var array The array that contains every event and their functions.
     */
    private static array $_events = array();

    /**
     * Check if provided parameters are presents in the GET or in the POST superglobal.
     * @param string ...$parameters
     * @return void
     * @throws InvalidArgumentException If ONE is missing.
     */
    public static function require(string...$parameters): void
    {
        foreach ($parameters as $arg) {
            if (!(isset($_GET[$arg]) || isset($_POST[$arg])))
                throw new InvalidArgumentException('Missing argument: ' . $arg . '!');
        }
    }

    /**
     * Whitelist function to filter a value against an array of allowed values.
     * @param mixed $value The value to be checked against the whitelist.
     * @param array $allowed An array containing allowed values.
     * @param mixed $default (Optional) The default value to return if $value is not in the whitelist.
     * @return mixed Returns $value if it's in the whitelist, otherwise returns $default or throws an exception if $default is not provided.
     * @throws InvalidArgumentException If $value is not in the whitelist and no $default is provided.
     * @see in_array()
     */
    public static function whitelist(mixed $value, array $allowed, mixed $default = null): mixed
    {
        if (in_array($value, $allowed, true))
            return $value;
        if (isset($default))
            return $default;
        throw new InvalidArgumentException('This value is no allowed here!');
    }

    /**
     * Sanitizes a string by replacing spaces with underscores and removing or replacing special characters.
     * @param string $string The input string to sanitize.
     * @param bool $lightweight Set to true for a lightweight sanitization (only replaces spaces with underscores, " a  B c @$  aBc " -> "_a__B_c_@$__aBc_") or false for a stricter sanitization (also removes or replaces special characters, " a  B c @$  aBc " -> "a__B_c_____aBc").
     * @return string The sanitized string.
     */
    public static function sanitize(string $string, bool $lightweight = true): string
    {
        return $lightweight ? preg_replace('/\s/', '_', $string) : preg_replace('/[^a-zA-Z0-9]/', '_', trim($string));
    }

    /**
     * Apply rScan on an entire directory, including sub-folders.
     * @param string $path Where to scan.
     * @return array An array containing detected files.
     */
    public static function recursiveScan(string $path): array
    {
        $r = array();
        $files = self::rScan($path);
        foreach ($files as $file) {
            if (is_dir($path . $file)) {
                foreach (self::recursiveScan($path . $file . '/') as $subFile)
                    $r[] = $subFile;
            } else $r[] = $path . $file;
        }

        return $r;
    }

    /**
     * Convenience method to use the native PHP function "scandir", exclude the first two results, namely the "." and the ".."
     * @param string $path Where to scan.
     * @return array An array containing detected files.
     * @link scandir()
     */
    public static function rScan(string $path): array
    {
        return array_diff(scandir($path), ['.', '..']);
    }

    /**
     * Creates a JavaScript function call string with the given name and arguments.
     * This function takes the name of a JavaScript function and an arbitrary number of arguments and generates a JavaScript function call string in the format: "name(arg1, arg2, ...)".
     * The arguments are processed to ensure they are properly formatted and can be safely used in JavaScript code.
     * String arguments are parsed, except if "noparse:" is used.
     * @param string $name The name of the JavaScript function.
     * @param mixed ...$args An arbitrary number of arguments to pass to the JavaScript function.
     * @return string A JavaScript function call string with the specified name and arguments.
     * @deprecated
     */
    public static function createFunctionJS(string $name, mixed...$args): string
    {
        $parseIfNecessary = function (mixed &$value): void {
            if (is_string($value)) {
                if (str_contains($value, 'noparse:'))
                    $value = str_replace('noparse:', R::EMPTY, $value);
                else self::parse($value);
            }
        };

        $joined = self::EMPTY;
        if (count($args) >= 1) {
            $parseIfNecessary($args[0]);
            $joined = $args[0];
            for ($i = 1; $i < count($args); $i++) {
                $joined .= ',';
                $parseIfNecessary($args[$i]);
                $joined .= $args[$i];
            }
        }

        return $name . '(' . $joined . ');';
    }

    /**
     * Parses a given string by wrapping it with a specified parser string.
     * @param string &$main The main string to be parsed.
     * @param string $parser The parser string used to wrap the main string (default is single quotes).
     * @return void
     */
    public static function parse(string &$main, string $parser = '\''): void
    {
        $main = $parser . $main . $parser;
    }

    /**
     * Clamp int|float within bounds.
     * @param int|float $value
     * @param int|float $min
     * @param int|float $max
     * @return int|float
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        self::checkArgument($max > $min, 'Min cannot be greater than max!');
        return $value < $min ? $min : min($value, $max);
    }

    /**
     * Convenience method to check if validity of a boolean argument.
     * @param bool $arg The boolean argument to be validated.
     * @param string $message A custom error message to be used when the argument is invalid.
     * @param bool $noException If set to true, exceptions will not be thrown, and the method will terminate the script with an echoed message instead.
     * @throws InvalidArgumentException If $arg is false and $noException is false.
     */
    public static function checkArgument(bool $arg, string $message = self::EMPTY, bool $noException = false): void
    {
        $message = self::blank($message) ? 'Provided argument is not valid!' : $message;
        if (!$arg) {
            if ($noException) {
                echo $message;
                die;
            } else throw new InvalidArgumentException($message);
        }
    }

    /**
     * Determines if a given value is considered "blank" based on various conditions.
     * Conditions checked:
     * 1. If the value is null, it is considered blank.
     * 2. If the value is a string, it is considered blank if its trimmed version is equal to the R::EMPTY constant.
     * 3. If the value is numeric or a boolean, it is not considered blank.
     * 4. If the value is an instance of Countable (e.g., an array), it is considered blank if it has zero elements.
     * 5. For other cases, the function uses the empty() function to check if the value is blank.
     * @param mixed &$value The value to check for blankness.
     * @return bool Returns true if the value is considered blank, otherwise returns false.
     * @see empty()
     */
    public static function blank(mixed &$value): bool
    {
        if (is_null($value))
            return true;
        if (is_string($value))
            return trim($value) === self::EMPTY;
        if (is_numeric($value) || is_bool($value))
            return false;
        if ($value instanceof Countable)
            return count($value) === 0;
        return empty($value);
    }

    /**
     * Retrieves a specified parameter from either the GET or POST superglobals (in this order).
     * @param string $parameter The name of the parameter to retrieve.
     * @param mixed $default The default value to return if the parameter is not set.
     * @param string $message Custom error message to display if the parameter is not set and no default is provided.
     * @param bool $noException If true, echoes the error message and terminates script execution; otherwise, throws an exception.
     * @return mixed The value of the specified parameter if set, default value if provided, or error handling based on $noException.
     * @throws InvalidArgumentException If the parameter is not set, no default value is provided, and $noException is false.
     * @see require()
     */
    public static function getParameter(string $parameter, mixed $default = null, string $message = R::EMPTY, bool $noException = false): mixed
    {
        $argument = R::EMPTY;
        $message = R::blank($message) ? 'The required parameter is not provided and no default value is given!' : $message;

        if (isset($_GET[$parameter]))
            $argument = $_GET[$parameter];
        else if (isset($_POST[$parameter]))
            $argument = $_POST[$parameter];

        if (!R::blank($argument))
            return $argument;
        if (isset($default))
            return $default;
        if ($noException) {
            echo $message;
            die;
        } else throw new InvalidArgumentException($message);
    }

    /**
     * Hacky way to cast an object to class.
     * Uses PHP serialization to transform an object to another.
     * @link https://gist.github.com/borzilleri/960035
     * @see https://www.php.net/manual/fr/function.serialize.php
     * @see https://www.php.net/manual/fr/function.unserialize.php
     * @param mixed $object The object you want to cast.
     * @param string $className The destination class name.
     * @return mixed The casted object.
     */
    public static function cast(mixed $object, string $className): mixed
    {
        self::checkArgument(is_object($object), '$object must be an object.');
        self::checkArgument(class_exists($className), sprintf('Unknown class: %s.', $className));
        // self::checkArgument(is_subclass_of($className, get_class($object), sprintf('%s is not a descendant of $object class: %s.', $className, get_class($object))));
        return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($className) . ':"' . $className . '"', serialize($object)));
    }

    /**
     * Prefixes all elements in the given array with the specified prefix.
     * @param string $prefix The prefix to add to each element.
     * @param string|array $input element(s) to be prefixed.
     * @return void
     */
    public static function prefix(string $prefix, string|array &$input): void
    {
        if (is_string($input))
            $input = $prefix . $input;
        if (is_array($input))
            array_walk($input, function (&$element) use ($prefix) {
                self::checkArgument(self::canBeString($element), 'The prefix function works only with strings!');
                $element = $prefix . $element;
            });
    }

    /**
     * Checks if the given value can be treated as a string.
     * @param mixed &$value The value to be checked.
     * @return bool True if the value is either a non-blank string, a scalar value, or an object with a '__toString' method; otherwise, returns false.
     */
    public static function canBeString(mixed &$value): bool
    {
        return is_string($value) || is_scalar($value) || $value instanceof Stringable;
    }

    /**
     * Saves a result of the first call then returns the cached value.
     * @param $target
     * @return __anonymous
     * @example memorize($user)->count();
     */
    public static function memorize($target): object
    {
        static $mem = new WeakMap();

        return new class ($target, $mem) {
            public function __construct(protected $target, protected &$mem)
            {
            }

            public function __call($method, $params)
            {
                $this->mem[$this->target] ??= [];
                $signature = $method . crc32(json_encode($params));
                return $this->mem[$this->target][$signature] ??= $this->target->$method(...$params);
            }
        };
    }

    /**
     * Primitive event system.
     * Allow you to bind a function to an event.
     * @param string $name The name of your event.
     * @param callable $function The function that will be executed when R::call($name) is called.
     * @return void
     * @see call
     * @see unbind
     * @example R::bind('myEvent', function(int $x) { return $x++; });
     */
    public static function bind(string $name, callable $function): void
    {
        self::checkArgument(!self::blank($name) && is_callable($function), 'Invalid event binding!');
        if (!isset(self::$_events[$name]))
            self::$_events[$name] = array();
        self::$_events[$name][] = $function;
    }

    /**
     * Primitive event system.
     * Allow you to delete an event.
     * @param string $name The name of your event.
     * @return void
     * @see call
     * @see bind
     * @example R::unbind('myEvent');
     */
    public static function unbind(string $name): void
    {
        unset(self::$_events[$name]);
    }

    /**
     * Primitive event system.
     * Allows you to perform each function bound to the provided event.
     * @param string $name The name of your event.
     * @return void
     * @see unbind
     * @see bind
     * @example R::call('myEvent', 2);
     */
    public static function call(string $name): void
    {
        self::checkArgument(!self::blank($name) && is_array(self::$_events[$name]), 'Invalid event name!');
        $args = func_get_args();
        array_shift($args);
        foreach (self::$_events[$name] as $callback)
            self::checkArgument(call_user_func_array($callback, $args) !== false, 'Event function failed!');
    }

    /**
     * Generates the next available path for a given destination path, avoiding overwriting.
     * @param string $destination The destination path where the file should be saved. Something like 'folder/fileName.ext'.
     * @param int $iteration (Optional) The iteration number to resolve potential naming conflicts.
     * @return string The next available filename to prevent overwriting existing files. Can create something like 'folder/fileName_2.ext'.
     */
    public static function nextName(string $destination, int $iteration = 0): string
    {
        $infos = self::pathInfo($destination);
        $tryName = $iteration == 0 ? $destination : self::concat(self::EMPTY, $infos['dirname'], '/', $infos['filename'], '_', $iteration, '.', $infos['extension']);
        return file_exists($tryName) ? self::nextName($destination, $iteration + 1) : $tryName;
    }

    /**
     * Convenient method to use pathinfo($path) and prevents unset values.
     * Extracts information from a file path and returns it as an associative array.
     * ```
     * $info = R::pathInfo('/www/htdocs/inc/lib.inc.php');   $info = R::pathInfo('/www/htdocs/inc/no_extension');
     * $info['dirname'] = '/www/htdocs/inc';                 $info['dirname'] = '/www/htdocs/inc';
     * $info['basename'] = 'lib.inc.php';                    $info['basename'] = 'lib.inc.php';
     * $info['extension'] = 'php';                           $info['extension'] = '';
     * $info['filename'] = 'lib.inc';                        $info['filename'] = 'no_extension';
     * ```
     * @param string $path The file path to extract information from.
     * @return array An associative array containing information about the file path, including 'dirname', 'basename', 'extension', and 'filename'.
     * @see pathinfo()
     */
    public static function pathInfo(string $path): array
    {
        $infos = pathinfo($path);
        $infos['dirname'] = isset($infos['dirname']) && $infos['dirname'] != '.' ? $infos['dirname'] : self::EMPTY;
        $infos['basename'] = $infos['basename'] ?? self::EMPTY;
        $infos['extension'] = $infos['extension'] ?? self::EMPTY;
        $infos['filename'] = $infos['filename'] ?? self::EMPTY;
        return $infos;
    }

    /**
     * Accepts strings and arrays of strings and adds everything together using a separator.
     * @param string $separator
     * @param mixed ...$strings Any string or string array.
     * @return string All values concatenated together.
     */
    public static function concat(string $separator, mixed...$strings): string
    {
        $strings = self::filterArray($strings);
        $main = self::EMPTY;

        foreach ($strings as $value) {
            self::checkArgument(self::canBeString($value) || is_array($value));
            if (self::canBeString($value))
                self::append($main, $separator, $value);
            else if (is_array($value)) {
                if (!self::blank($main))
                    $main .= $separator;
                $main .= self::concat($separator, ...$value);
            }
        }

        return $main;
    }

    /**
     * Filters an array, removing blank values.
     * This method takes an array as input and uses the array_filter function to remove elements that are considered blank.
     * The definition of blank is determined by the R::blank function.
     * @param array $array The input array to be filtered.
     * @return array The filtered array containing only non-blank values.
     * @see blank()
     * @see array_filter()
     */
    public static function filterArray(array $array): array
    {
        return array_filter($array, function (mixed $string) {
            return !R::blank($string);
        });
    }

    /**
     * Concatenates multiple strings with a specified separator into a main string.
     * @param string &$main The main string to concatenate into.
     * @param string $separator The separator to place between concatenated strings.
     * @param string ...$strings An array of strings to concatenate.
     * @return void
     * @see implode()
     */
    public static function append(string &$main, string $separator, string...$strings): void
    {
        $strings = self::filterArray($strings);
        $main = !self::blank($main) ? $main : self::EMPTY;

        if (count($strings) > 0) {
            $main = !R::blank($main) ? $main . $separator . $strings[0] : $strings[0];
            for ($i = 1; $i < count($strings); $i++) {
                if (!self::blank($strings[$i]))
                    $main .= $separator . $strings[$i];
            }
        }
    }

    /**
     * Resizes the image to the specified dimensions (keep the ratio if the height is not provided), and saves the resized image (name is suffixed with the new dimensions).
     * It supports common image formats such as JPEG, PNG, BMP, and WEBP.
     * @param string $path The path to the image file to be resized.
     * @param int $newWidth The new width of the image.
     * @param int|null $newHeight The new height of the image (optional).
     * @throws InvalidArgumentException If the provided image path is invalid, or if the image cannot be loaded and processed.
     */
    public static function resize(string $path, int $newWidth, int $newHeight = null): void
    {
        $type = exif_imagetype($path);
        R::checkArgument(in_array($type, [2/* JPEG */, 3/* PNG */, 6/* BMP */, 18/* WEBP */]));

        $imageData = file_get_contents($path);
        R::checkArgument($imageData !== false);

        $image = imagecreatefromstring($imageData);
        R::checkArgument($image instanceof GdImage);

        $width = imagesx($image);
        $height = imagesy($image);

        if (!isset($newHeight)) {
            $ratio = $width / $height;
            if ($width > $height)
                $newHeight = floor($newWidth / $ratio);
            else {
                $newHeight = $newWidth;
                $newWidth = floor($newWidth * $ratio);
            }
        }

        $comp = imagecreatetruecolor($newWidth, $newHeight);
        R::checkArgument($comp instanceof GdImage);

        if ($type == 1 || $type == 3) {
            imagecolortransparent($comp, imagecolorallocate($comp, 0, 0, 0));
            if ($type == 3) {
                imagealphablending($comp, false);
                imagesavealpha($comp, true);
            }
        }

        R::checkArgument(imagecopyresampled($comp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height));

        $name = $path;
        $suffix = '_' . $newWidth . 'x' . ($newHeight ?? $newWidth);
        self::suffix($suffix, $name);
        switch ($type) {
            case 2:
                imagejpeg($comp, $name, 100);
                break;
            case 3:
                imagepng($comp, $name, 0);
                break;
            case 18:
                imagewebp($comp, $name);
                break;
            case 6:
                imagebmp($comp, $name);
                break;
        }
    }

    /**
     * Suffix all elements in the given array with the specified suffix.
     * Keep the extension.
     * @param string $suffix The prefix to add to each element.
     * @param string|array $input element(s) to be suffixed.
     * @return void
     */
    public static function suffix(string $suffix, string|array &$input): void
    {
        $buildValue = function ($suffix, &$input): void {
            $infos = R::pathInfo($input);
            $input = $infos['dirname'] . ($infos['dirname'] == R::EMPTY ? R::EMPTY : '/') . $infos['filename'] . $suffix . ($infos['extension'] == R::EMPTY ? R::EMPTY : '.' . $infos['extension']);
        };

        if (is_string($input))
            $buildValue($suffix, $input);

        if (is_array($input))
            array_walk($input, function (&$element) use ($suffix, $buildValue) {
                self::checkArgument(self::canBeString($element), 'The suffix function works only with strings!');
                $buildValue($suffix, $element);
            });
    }

    /**
     * Checks if all the given keys are in the provided table. Does not check the associated value.
     * @param array $array The array to verify.
     * @param array $keys The keys whose presence must be checked.
     * @return bool True only if all array_key_exists($key, $array) is true.
     * @see array_key_exists()
     */
    public static function allKeysExist(array $array, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array))
                return false;
        }

        return true;
    }

    /**
     * Checks if the provided array is a square array, meaning that it has the same number of rows and columns.
     * @param array $array The array to be checked.
     * @return int|bool The size of the square array if it is square, or false otherwise.
     */
    public static function isSquareArray(array $array): int|bool
    {
        R::checkArgument(!empty($array));

        $amount = count($array[0]);
        foreach ($array as $column) {
            if ($amount != count($column))
                return false;
        }

        return $amount;
    }

    /**
     * Replace the first occurrence of a specified search string with a replacement in the given subject string.
     * @param string $search The string to search for.
     * @param string $replace The string to replace the first occurrence of the search string with.
     * @param string $subject The original string in which to perform the replacement.
     * @return string The modified string with the first occurrence of the search string replaced.
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        $search = '/' . preg_quote($search, '/') . '/';
        return preg_replace($search, $replace, $subject, 1);
    }

    /**
     * Function to swap the values of two variables.
     * This function takes two variables by reference and swaps their values.
     * If the two variables are equal, no action is taken.
     * @param mixed &$left The first variable to be swapped.
     * @param mixed &$right The second variable to be swapped.
     * @return void
     */
    public static function swap(mixed &$left, mixed &$right): void
    {
        if ($left === $right)
            return;
        $bucket = $left;
        $left = $right;
        $right = $bucket;
    }

    /**
     *  This PHP code "opens a drawer" whose have methods to manipulate nested arrays in a fluent manner.
     *  The 'Drawer' has the following methods:
     *  - add(mixed $element): Adds an element to the current level of the nested array and returns the Drawer object.
     *  - open(): Creates and returns a new Drawer object for nesting and adds it to the current level of the nested array.
     *  - close(): Closes the current level of nesting and returns either the parent Drawer object or the final nested array.
     *  - get(): Returns the nested array represented by the Drawer object.
     *  The class uses a private nested class to implement the fluent interface for creating nested arrays.
     *
     *  Example Usage:
     *  ```
     *  $drawer = Drawer::openDrawer();
     *  $nestedArray = $drawer
     *      ->add("Element 1")
     *      ->open()
     *          ->add("Element 2")
     *          ->add("Element 3")
     *      ->close()
     *      ->add("Element 4")
     *      ->get();
     *  ```
     *
     *  The resulting $nestedArray will be an array with the following structure:
     *  ```
     *  [
     *      "Element 1",
     *      [
     *          "Element 2",
     *          "Element 3",
     *      ],
     *      "Element 4",
     *  ]
     * ```
     */
    public static function openDrawer(): object
    {
        return new class (null) {
            private ?object $_parent;
            private array $_stack;

            public function __construct(?object $parent)
            {
                $this->_parent = $parent;
                $this->_stack = [];
            }

            public function add(mixed $element): object
            {
                $this->_stack[] = $element;
                return $this;
            }

            public function open(): object
            {
                $nestedArray = new self($this);
                $this->_stack[] = $nestedArray;
                return $nestedArray;
            }

            public function close(): object|array
            {
                return $this->_parent ?? $this->get();
            }

            public function get(): array
            {
                if (isset($this->_parent))
                    $array = $this->_parent->get();
                else $array = $this->get_IMPL($this->_stack);
                return $array;
            }

            private function get_IMPL(array $values): array
            {
                $array = [];
                foreach ($values as $element) {
                    if ($element instanceof self)
                        $array[] = $element->get_IMPL($element->_stack);
                    else $array[] = $element;
                }

                return $array;
            }
        };
    }

    /**
     * Takes a multidimensional array and flattens it to a one-dimensional array.
     * @param array $array The input multidimensional array to be flattened.
     * @return array The flattened one-dimensional array.
     */
    public static function flattenArray(array $array): array
    {
        $array = [];
        array_walk_recursive($array, function ($a) use (&$array) {
            $array[] = $a;
        });
        return $array;
    }
}