<?php
namespace Glit\GitoliteBundle\Utils;

/**
 * SHA regulates all SHA strings used in Git
 */
class SHA {

    protected
        $bin = null;

    function __construct($sha = null) {
        if (!is_null($sha)) {
            if (is_numeric("0x" . $sha) && strlen($sha) == 40) {
                // hex sha value
                $this->bin = (string)pack('H40', $sha);
            }
            else
            {
                $hex = bin2hex($sha);
                if (is_numeric("0x" . $hex) && strlen($hex) == 40) {
                    $this->bin = (string)$sha;
                }
                else
                {
                    throw new Exception("SHA accepts only a valid hex or bin SHA string as argument, supplied '" . $sha . "'");
                }
            }
        }
    }

    public function fromData($data) {
        $this->bin = (string)self::hash($data);
    }

    public function h($count = null) {
        return is_null($count) ? $this->hex() : substr($this->hex(), 0, $count);
    }

    public function b64() {
        return base64_encode($this->bin());
    }

    public function b() {
        return $this->bin();
    }

    public function __toString() {
        return $this->bin();
    }

    public function bin() {
        if (is_null($this->bin)) {
            throw new Exception("The SHA hash is not computed");
        }
        return $this->bin;
    }

    public function hex() {
        return bin2hex($this->bin);
    }

    static public function hash($data, $raw = true) {
        return new SHA(hash('sha1', $data, $raw));
    }
}