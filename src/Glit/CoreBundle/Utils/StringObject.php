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

    public static function staticStartsWith($haystack, $needle, $case) {
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
}