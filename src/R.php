<?php

/**
 * Utility Functions!
 * This PHP file contains a collection of useful functions for various tasks.
 * Feel free to use this file in your projects, but please be aware that it comes with no warranties or guarantees. You are responsible for testing and using these functions at your own risk.
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 18
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
    private static array $_events = [];

    private static array $_exisfImageType = [2/* JPEG */, 3/* PNG */, 6/* BMP */, 18/* WEBP */];

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
     * Check if provided parameter is presents in the GET or in the POST superglobal.
     * @param string $parameter
     * @return bool
     */
    public static function optional(string $parameter): bool
    {
        return isset($_GET[$parameter]) || isset($_POST[$parameter]);
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
        $r = [];
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
                    $value = str_replace('noparse:', self::EMPTY, $value);
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
     * Linearly interpolates between two values.
     * This method calculates a value between $min and $max based on the given $value.
     * The result is interpolated linearly within the range [$min, $max].
     * @param int|float $min The minimum value.
     * @param int|float $max The maximum value.
     * @param int|float $value The interpolation factor. Should be between 0 and 1.
     * @return int|float The interpolated value.
     * @throws InvalidArgumentException If $min is greater than $max.
     */
    public static function lerp(int|float $min, int|float $max, int|float $value): int|float
    {
        self::checkArgument($max > $min, 'Min cannot be greater than max!');
        return $min + ($max - $min) * self::clamp($value, 0.0, 1.0);
    }

    /**
     * Convenience method to check the validity of a boolean argument.
     * @param bool $argument The boolean argument to be validated.
     * @param string $message A custom error message to be used when the argument is invalid.
     * @param bool $throwException If set to true, exceptions will be thrown.
     * @throws InvalidArgumentException If $arg is false and $noException is false.
     */
    public static function checkArgument(bool $argument, string $message = self::EMPTY, bool $throwException = true): void
    {
        $message = self::blank($message) ? 'Provided argument is not valid!' : $message;
        if (!$argument) {
            if ($throwException)
                throw new InvalidArgumentException($message);
            echo $message;
            die;
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
     * Remaps a value from one range to another.
     * This method takes a value within a given range (originalMin to originalMax) and remaps it to a new range (destinationMin to destinationMax).
     * @param int|float $value The value to be remapped.
     * @param int|float $originalMin The minimum value of the original range.
     * @param int|float $originalMax The maximum value of the original range.
     * @param int|float $destinationMin The minimum value of the destination range.
     * @param int|float $destinationMax The maximum value of the destination range.
     * @return int|float The remapped value.
     * @throws InvalidArgumentException If either the original range or the destination range is invalid.
     */
    public static function remap(int|float $value, int|float $originalMin, int|float $originalMax, int|float $destinationMin, int|float $destinationMax): int|float
    {
        self::checkArgument($originalMax > $originalMin, 'Min cannot be greater than max!');
        self::checkArgument($destinationMax > $destinationMin, 'Min cannot be greater than max!');
        return $destinationMin + ($value - $originalMin) * ($destinationMax - $destinationMin) / ($originalMax - $originalMin);
    }

    /**
     * Retrieves a specified parameter from either the GET or POST superglobals (in this order).
     * If the parameter isn't found and no default is provided, will either throw an exception or display a message.
     * - The type of the value found is not checked.
     * @param string $parameter The name of the parameter to retrieve.
     * @param mixed $default The default value to return if the parameter is not set.
     * @param string $message The message to show, if it is empty, nothing is displayed.
     * @param bool $throwException If true, throws an exception; otherwise echoes the error message and terminates script execution with die.
     * @return mixed The value of the requested parameter, or the default value if provided.
     * @throws InvalidArgumentException If the parameter is not provided, and no default value is given (only if $throwException is true).
     */
    public static function getParameter(string $parameter, mixed $default = null, string $message = R::EMPTY, bool $throwException = true): mixed
    {
        $argument = self::EMPTY;

        if (isset($_GET[$parameter]))
            $argument = $_GET[$parameter];
        else if (isset($_POST[$parameter]))
            $argument = $_POST[$parameter];

        if (!self::blank($argument))
            return $argument;
        if (isset($default))
            return $default;

        if ($throwException) {
            $message = self::blank($message) ? 'The required parameter is not provided and no default value is given!' : $message;
            throw new InvalidArgumentException($message);
        }

        if (!self::blank($message))
            echo $message;
        die;
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
     * ```
     * R::bind('myEvent', function(int $x) { return $x++; });
     * ```
     * @param string $name The name of your event.
     * @param callable $function The function that will be executed when R::call($name) is called.
     * @return void
     * @see call
     * @see unbind
     */
    public static function bind(string $name, callable $function): void
    {
        self::checkArgument(!self::blank($name) && is_callable($function), 'Invalid event binding!');
        if (!isset(self::$_events[$name]))
            self::$_events[$name] = [];
        self::$_events[$name][] = $function;
    }

    /**
     * Primitive event system.
     * Allow you to delete an event.
     * ```
     * R::unbind('myEvent');
     * ```
     * @param string $name The name of your event.
     * @return void
     * @see call
     * @see bind
     */
    public static function unbind(string $name): void
    {
        unset(self::$_events[$name]);
    }

    /**
     * Primitive event system.
     * Allows you to perform each function bound to the provided event.
     * ```
     * R::call('myEvent', 2);
     * ```
     * @param string $name The name of your event.
     * @return void
     * @see unbind
     * @see bind
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
            return !self::blank($string);
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
            $main = !self::blank($main) ? $main . $separator . $strings[0] : $strings[0];
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
     * @return string The name of the generated file.
     * @throws InvalidArgumentException If the provided image path is invalid, or if the image cannot be loaded and processed.
     */
    public static function resizeImage(string $path, int $newWidth, int $newHeight = null): string
    {
        $type = exif_imagetype($path);
        self::checkArgument(in_array($type, self::$_exisfImageType));

        $image = self::resize_IMPL($path, $newWidth, $newHeight);

        $name = $path;
        $suffix = '_' . $newWidth . 'x' . ($newHeight ?? $newWidth);
        self::suffix($suffix, $name);
        switch ($type) {
            case 2:
                imagejpeg($image, $name, 100);
                break;
            case 3:
                imagepng($image, $name, 0);
                break;
            case 18:
                imagewebp($image, $name);
                break;
            case 6:
                imagebmp($image, $name);
                break;
        }

        return $name;
    }

    private static function resize_IMPL(string $path, int $newWidth, int $newHeight = null): GDImage
    {
        $type = exif_imagetype($path);
        self::checkArgument(in_array($type, self::$_exisfImageType));

        $imageData = file_get_contents($path);
        self::checkArgument($imageData !== false);

        $image = imagecreatefromstring($imageData);
        self::checkArgument($image instanceof GdImage);

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
        self::checkArgument($comp instanceof GdImage);

        if ($type == 1 || $type == 3) {
            imagecolortransparent($comp, imagecolorallocate($comp, 0, 0, 0));
            if ($type == 3) {
                imagealphablending($comp, false);
                imagesavealpha($comp, true);
            }
        }

        self::checkArgument(imagecopyresampled($comp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height));

        return $comp;
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
            $infos = self::pathInfo($input);
            $input = $infos['dirname'] . ($infos['dirname'] == self::EMPTY ? self::EMPTY : '/') . $infos['filename'] . $suffix . ($infos['extension'] == self::EMPTY ? self::EMPTY : '.' . $infos['extension']);
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
     * @param string $source The first image.
     * @param string $other The second image.
     * @param int $precision A value between 1 and 6.
     * @return float The percentage of correspondence between the two images.
     */
    public static function compareImages(string $source, string $other, int $precision = 3): float
    {
        $type = exif_imagetype($source);
        self::checkArgument(in_array($type, self::$_exisfImageType));

        $type = exif_imagetype($other);
        self::checkArgument(in_array($type, self::$_exisfImageType));

        $size = pow(2, self::clamp($precision, 0, 6));

        $rs = self::resize_IMPL($source, $size, $size);
        $ro = self::resize_IMPL($other, $size, $size);

        $same = 0;
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $cs = imagecolorat($rs, $x, $y);
                $co = imagecolorat($ro, $x, $y);
                if ($cs == $co)
                    $same++;
            }
        }

        return $same / ($size * $size);
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
        self::checkArgument(!empty($array));

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
     *  The class uses a private nested class to implement the fluent interface for creating nested arrays.
     *  The 'Drawer' has the following methods:
     *  - add(mixed $element): Adds an element to the current level of the nested array and returns the Drawer object.
     *  - open(): Creates and returns a new Drawer object for nesting and adds it to the current level of the nested array.
     *  - close(): Closes the current level of nesting and returns either the parent Drawer object or the final nested array.
     *  - get(): Returns the final nested array represented by the Drawer object.
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
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    /**
     * Check if at least one element in the iterable satisfies the given condition.
     * ```
     * R::one($values, fn($v) => $v > 1)
     * ```
     * @param iterable $values The iterable to check.
     * @param callable $function The condition to apply to each element. It should return a boolean.
     * @return bool True if at least one element satisfies the condition, false otherwise.
     */
    public static function one(iterable $values, callable $function): bool
    {
        foreach ($values as $item)
            if ($function($item))
                return true;
        return false;
    }

    /**
     * Determines if half OR more of the elements in the provided iterable satisfy a given condition.
     * ```
     * R::half($values, fn($v) => $v > 1)
     * ```
     * @param iterable $values The iterable containing the elements to be evaluated.
     * @param callable $function The callback function used to test each element. It should return a boolean.
     * @param bool $strict Optional. If set to true, strict comparison is used to determine if MORE than half of the elements satisfy the condition.
     * @return bool True if more than half of the elements satisfy the condition, false otherwise.
     */
    public static function half(iterable $values, callable $function, bool $strict = false): bool
    {
        $size = 0;
        $validate = 0;

        foreach ($values as $item) {
            $size++;
            if ($function($item))
                $validate++;
        }

        return $strict ? $validate > ($size / 2) : $validate >= ($size / 2);
    }

    /**
     * Checks if all elements in the provided iterable satisfy the given condition.
     * ```
     * R::all($values, fn($v) => $v > 1)
     * ```
     * @param iterable $values The iterable to check.
     * @param callable $function The condition to be satisfied by each element. It should return a boolean.
     * @return bool True if all elements satisfy the condition, false otherwise.
     */
    public static function all(iterable $values, callable $function): bool
    {
        foreach ($values as $item)
            if (!$function($item))
                return false;
        return true;
    }

    /**
     * Generates a version 4 UUID.
     * @return string The generated UUID.
     * @throws \Random\RandomException
     * @link
     */
    public static function getUUID(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // Set version to 0100
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Purifies the input string by encoding HTML entities and special characters.
     * This method sanitizes the input string by converting special characters to their corresponding HTML entities to prevent XSS attacks and maintain data integrity.
     * @param string $unpurified The string to be purified.
     * @return void
     * @see unpurify
     */
    public static function purify(string &$unpurified): void
    {
        $unpurified = htmlentities(htmlspecialchars($unpurified));
    }

    /**
     * Unpurifies the previously purified string by decoding HTML entities and special characters.
     * This method reverses the purification process by decoding HTML entities and special characters back to their original form, restoring the string's original state.
     * @param string $purified The purified string to be unpurified.
     * @return void
     * @see purify
     */
    public static function unpurify(string &$purified): void
    {
        $purified = html_entity_decode(htmlspecialchars_decode($purified));
    }
}