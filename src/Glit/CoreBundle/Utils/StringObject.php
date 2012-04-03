<?php
namespace Glit\CoreBundle\Utils;

class StringObject {

    private $string;

    public function __construct($string) {
        $this->string = $string;
    }

    public function startsWith($needle, $case = true) {
        if ($case) {
            return strpos($this->string, $needle, 0) === 0;
        }

        return stripos($this->string, $needle, 0) === 0;
    }

    public function endsWith($needle, $case = true) {
        $expectedPosition = strlen($this->string) - strlen($needle);

        if ($case) {
            return strrpos($this->string, $needle, 0) === $expectedPosition;
        }

        return strripos($this->string, $needle, 0) === $expectedPosition;
    }

    public function explode($delimiter) {
        return explode($delimiter, $this->string);
    }

    public function __toString() {
        return $this->string;
    }
}