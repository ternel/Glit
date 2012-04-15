<?php
namespace Glit\CoreBundle\Utils;

/**
 * Standardization of string manipulation
 */
class StringObject {

    private $string;

    public function __construct($string) {
        $this->string = $string;
    }

    public function startsWith($needle, $case = true) {
        return self::staticStartsWith($this->string, $needle, $case);
    }

    public function endsWith($needle, $case = true) {
        return self::staticEndsWith($this->string, $needle, $case);
    }

    public function explode($delimiter) {
        return self::staticExplode($this->string, $delimiter);
    }

    public function __toString() {
        return $this->string;
    }

    public static function staticExplode($haystack, $delimiter) {
        return explode($delimiter, $haystack);
    }

    public static function staticStartsWith($haystack, $needle, $case = true) {
        if ($case) {
            return strpos($haystack, $needle, 0) === 0;
        }

        return stripos($haystack, $needle, 0) === 0;
    }

    public static function staticEndsWith($haystack, $needle, $case = true) {
        $expectedPosition = strlen($haystack) - strlen($needle);

        if ($case) {
            return strrpos($haystack, $needle, 0) === $expectedPosition;
        }

        return strripos($haystack, $needle, 0) === $expectedPosition;
    }

    /**
     * Count the number of bytes of a given string.
     * Input string is expected to be ASCII or UTF-8 encoded.
     * Warning: the function doesn't return the number of chars
     * in the string, but the number of bytes.
     *
     * @param string $str The string to compute number of bytes
     *
     * @return mixed The length in bytes of the given string.
     */
    public static function staticStrBytes($str) {
        // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT

        // Number of characters in string
        $strlen_var = strlen($str);

        // string bytes counter
        $d = 0;

        /*
        * Iterate over every character in the string,
        * escaping with a slash or encoding to UTF-8 where necessary
        */
        for ($c = 0; $c < $strlen_var; ++$c) {

            $ord_var_c = ord($str{$d});

            switch (true) {
                case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                    // characters U-00000000 - U-0000007F (same as ASCII)
                    $d++;
                    break;

                case (($ord_var_c & 0xE0) == 0xC0):
                    // characters U-00000080 - U-000007FF, mask 110XXXXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $d += 2;
                    break;

                case (($ord_var_c & 0xF0) == 0xE0):
                    // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $d += 3;
                    break;

                case (($ord_var_c & 0xF8) == 0xF0):
                    // characters U-00010000 - U-001FFFFF, mask 11110XXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $d += 4;
                    break;

                case (($ord_var_c & 0xFC) == 0xF8):
                    // characters U-00200000 - U-03FFFFFF, mask 111110XX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $d += 5;
                    break;

                case (($ord_var_c & 0xFE) == 0xFC):
                    // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $d += 6;
                    break;
                default:
                    $d++;
            }
        }

        return $d;
    }
}