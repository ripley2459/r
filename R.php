<?php

/**
 * Utility Functions!
 * This PHP file contains a collection of useful functions for various tasks.
 * Feel free to use this file in your projects, but please be aware that it comes with no warranties or guarantees. You are responsible for testing and using these functions at your own risk.
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 7
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
    private static array $_events;

    /**
     * Check if provided arguments are presents in the GET or in the POST method.
     * @param string ...$args
     * @return void
     * @throws InvalidArgumentException If ONE is missing.
     */
    public static function require(string...$args): void
    {
        foreach ($args as $arg) {
            if (!(isset($_GET[$arg]) || isset($_POST[$arg]))) throw new InvalidArgumentException('Missing argument: ' . $arg . '!');
        }
    }

    /**
     * Whitelist function to filter a value against an array of allowed values.
     * @param mixed $value The value to be checked against the whitelist.
     * @param array $allowed An array containing allowed values.
     * @param mixed $default (Optional) The default value to return if $value is not in the whitelist.
     * @return mixed Returns $value if it's in the whitelist, otherwise returns $default or throws an exception if $default is not provided.
     * @throws InvalidArgumentException If $value is not in the whitelist and no $default is provided.
     */
    public static function whitelist(mixed $value, array $allowed, mixed $default = null): mixed
    {
        if (in_array($value, $allowed, true)) return $value;
        if (isset($default)) return $default;
        throw new InvalidArgumentException('This value is no allowed here!');
    }

    /**
     * Sanitizes a string by replacing spaces with underscores and removing or replacing special characters.
     * @param string $string The input string to sanitize.
     * @param bool $lightweight Set to true for a lightweight sanitization (only replaces spaces with underscores, "a  B c @$  aBc" -> "a_B_c_@$_aBc") or false for a stricter sanitization (also removes or replaces special characters, "a  B c @$  aBc" -> "a__B_c____aBc").
     * @return string The sanitized string.
     */
    public static function sanitize(string $string, bool $lightweight = true): string
    {
        return $lightweight ? preg_replace('/\s+/', '_', $string) : strtolower(preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $string));
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
                foreach (self::recursiveScan($path . $file . '/') as $subFile) {
                    $r[] = $subFile;
                }
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
     */
    public static function createFunctionJS(string $name, mixed...$args): string
    {
        $parseIfNecessary = function (mixed &$value): void {
            if (is_string($value)) {
                if (str_contains($value, 'noparse:')) $value = str_replace('noparse:', R::EMPTY, $value);
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
     * Convenience method to check if the given argument is true and throw an exception if not.
     * @param bool $arg The argument to check.
     * @param string $message Message for the exception.
     * @return void
     */
    public static function checkArgument(bool $arg, string $message = self::EMPTY): void
    {
        if (!$arg) throw new InvalidArgumentException($message);
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
        if (is_string($input)) $input = $prefix . $input;
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
        self::$_events[$name][] = $function;
    }

    /**
     * Checks if a given value is blank (null, empty, equals to R::Empty or count === 0).
     * @param mixed &$value The value to be checked.
     * @return bool Returns true if the value is null or empty, otherwise false.
     */
    public static function blank(mixed &$value): bool
    {
        if (is_null($value)) return true;
        if (is_string($value)) return trim($value) === self::EMPTY;
        if (is_numeric($value) || is_bool($value)) return false;
        if ($value instanceof Countable) return count($value) === 0;
        return empty($value);
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
        self::checkArgument(!self::blank($name) && is_callable(self::$_events[$name]), 'Invalid event name!');
        $args = func_get_args();
        array_shift($args);
        self::checkArgument(call_user_func_array(self::$_events[$name], $args) !== false, 'Event function failed!');
    }

    /**
     * Generates the next available path for a given destination path, avoiding overwrites.
     * @param string $destination The destination path where the file should be saved. Something like 'folder/fileName.ext'.
     * @param int $iteration (Optional) The iteration number to resolve potential naming conflicts.
     * @return string The next available filename to prevent overwriting existing files. Can create something like 'folder/fileName_2.ext'.
     */
    public static function nextName(string $destination, int $iteration = 0): string
    {
        $infos = pathinfo($destination);
        $tryName = $iteration == 0 ? $destination : self::concat(self::EMPTY, $infos['dirname'], '/', $infos['filename'], '_', $iteration, '.', $infos['extension']);
        return file_exists($tryName) ? self::nextName($destination, $iteration + 1) : $tryName;
    }

    /**
     * Accepts strings and arrays of strings and adds everything together using a separator.
     * @param string $separator
     * @param mixed ...$values Any string or string array.
     * @return string All values concatenated together.
     */
    public static function concat(string $separator, mixed...$values): string
    {
        $main = self::EMPTY;
        foreach ($values as $value) {
            self::checkArgument(self::canBeString($value) || is_array($value));
            if (self::canBeString($value)) self::append($main, $separator, $value);
            else if (is_array($value)) {
                if (!self::blank($main)) $main .= $separator;
                $main .= self::concat($separator, ...$value);
            }
        }

        return $main;
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
        $main = !self::blank($main) ? $main : self::EMPTY;

        if (count($strings) > 0) {
            $main = !R::blank($main) ? $main . $separator . $strings[0] : $strings[0];
            for ($i = 1; $i < count($strings); $i++) {
                $main .= $separator . $strings[$i];
            }
        }
    }

    /**
     * Compresses an image file, reducing its dimensions based on a specified compression percentage, and saves the compressed image with a new suffix.
     * @param string $path The path to the original image file.
     * @param string $suffix The suffix to add to the filename of the compressed image.
     * @param int $compression The compression percentage ]0;10[ to apply to the image.
     * @throws InvalidArgumentException if the provided file is not a supported image type, or if any errors occur during image processing.
     */
    public static function compress(string $path, string $suffix, int $compression): void
    {
        R::checkArgument($compression >= 1 && $compression < 100);

        $mimeType = mime_content_type($path);
        R::checkArgument(in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp']));

        $imageData = file_get_contents($path);
        R::checkArgument($imageData !== false);

        $image = imagecreatefromstring($imageData);
        R::checkArgument($image instanceof GdImage);

        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = round($width - ($width * $compression) / 100);
        $newHeight = round($height - ($height * $compression) / 100);
        $comp = imagecreatetruecolor($newWidth, $newHeight);
        R::checkArgument($comp instanceof GdImage);
        R::checkArgument(imagecopyresized($comp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height));

        $name = $path;
        self::suffix($suffix, $name);
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($comp, $name);
                break;
            case 'image/png':
                imagepng($comp, $name);
                break;
            case 'image/gif':
                imagegif($comp, $name);
                break;
            case 'image/webp':
                imagewebp($comp, $name);
                break;
            case 'image/bmp':
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
            $infos = R::pathInfos($input);
            $input = $infos['dirname'] . ($infos['dirname'] == R::EMPTY ? R::EMPTY : '/') . $infos['filename'] . $suffix . ($infos['extension'] == R::EMPTY ? R::EMPTY : '.' . $infos['extension']);
        };

        if (is_string($input)) $buildValue($suffix, $input);

        if (is_array($input))
            array_walk($input, function (&$element) use ($suffix, $buildValue) {
                self::checkArgument(self::canBeString($element), 'The suffix function works only with strings!');
                $buildValue($suffix, $element);
            });
    }

    /**
     * Convenient method to use pathinfo($path) and prevents unset values.
     * Extracts information from a file path and returns it as an associative array.
     * @param string $path The file path to extract information from.
     * @return array An associative array containing information about the file path, including 'dirname', 'basename', 'extension', and 'filename'.
     * @see pathinfo()
     */
    public static function pathInfos(string $path): array
    {
        $infos = pathinfo($path);
        $infos['dirname'] = isset($infos['dirname']) && $infos['dirname'] != '.' ? $infos['dirname'] : self::EMPTY;
        $infos['basename'] = $infos['basename'] ?? self::EMPTY;
        $infos['extension'] = $infos['extension'] ?? self::EMPTY;
        $infos['filename'] = $infos['filename'] ?? self::EMPTY;
        return $infos;
    }
}