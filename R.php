<?php

class R
{
    /**
     * Empty string that equal '';
     */
    public const EMPTY = '';

    /**
     * Space string that equal ' ';
     */
    public const SPACE = ' ';

    /**
     * Check if provided arguments are presents in the GET or in the POST method.
     * @param string ...$args
     * @return void
     * @throws InvalidArgumentException If ONE is missing.
     */
    public static function require(string...$args): void
    {
        foreach ($args as $arg) {
            if (!($_GET[$arg] || $_POST[$arg])) {
                throw new InvalidArgumentException('Missing argument: ' . $arg . '!');
            }
        }
    }

    /**
     * Whitelist function to filter a value against an array of allowed values.
     * @param mixed $value The value to be checked against the whitelist.
     * @param array $allowed An array containing allowed values.
     * @param mixed $default (Optional) The default value to return if $value is not in the whitelist.
     * @return mixed Returns $value if it is in the whitelist, otherwise returns $default or throws an exception if $default is not provided.
     * @throws InvalidArgumentException If $value is not in the whitelist and no $default is provided.
     */
    public static function whitelist(mixed $value, array $allowed, mixed $default = null): mixed
    {
        if (in_array($value, $allowed, true)) return $value;

        if (isset($default)) {
            return $default;
        } else {
            throw new InvalidArgumentException('This value is no allowed here!');
        }
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
            } else {
                $r[] = $path . $file;
            }
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
     * Checks whether a given string is either null or empty (contains only whitespace).
     * @param string $string The string to check.
     * @return bool Returns true if the string is null or contains only whitespace; otherwise, returns false.
     */
    public static function nullOrEmpty(string $string): bool
    {
        return !isset($string) || trim($string) === self::EMPTY;
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
        $joined = self::EMPTY;
        if (count($args) >= 1) {
            if (is_string($args[0])) self::parse($args[0]);
            $joined = $args[0];
            for ($i = 1; $i < count($args); $i++) {
                $joined .= ',';
                if (is_string($args[$i])) {
                    if (str_contains($args[$i], 'noparse:')) {
                        $args[$i] = str_replace('noparse:', R::EMPTY, $args[$i]);
                    } else {
                        self::parse($args[$i]);
                    }
                }

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
}