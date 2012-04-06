<?php
namespace Glit\GitoliteBundle\Utils;

/**
 * SHA regulates all SHA strings used in Git
 */
class SHA {

    /**
     * @relates Git
     * @brief Convert a SHA-1 hash from hexadecimal to binary representation.
     *
     * @param $hex (string) The hash in hexadecimal representation.
     * @returns (string) The hash in binary representation.
     */
    public static function sha1_bin($hex) {
        return pack('H40', $hex);
    }

    /**
     * @relates Git
     * @brief Convert a SHA-1 hash from binary to hexadecimal representation.
     *
     * @param $bin (string) The hash in binary representation.
     * @returns (string) The hash in hexadecimal representation.
     */
    public static function sha1_hex($bin) {
        return bin2hex($bin);
    }

}