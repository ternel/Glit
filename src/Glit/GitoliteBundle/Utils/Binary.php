<?php
namespace Glit\GitoliteBundle\Utils;

/**
 * Utility functions for dealing with binary files/strings.
 * All functions assume network byte order (big-endian).
 */
final class Binary {

    static public function uInt16($str, $pos = 0) {
        return ord($str{$pos + 0}) << 8 | ord($str{$pos + 1});
    }

    static public function uInt32($str, $pos = 0) {
        $a = unpack('Nx', substr($str, $pos, 4));
        return $a['x'];
    }

    static public function nuInt32($n, $str, $pos = 0) {
        $r = array();
        for ($i = 0; $i < $n; $i++, $pos += 4)
        {
            $r[] = Binary::uInt32($str, $pos);
        }
        return $r;
    }

    static public function fuInt32($f) {
        return Binary::uInt32(fread($f, 4));
    }

    static public function nfuInt32($n, $f) {
        return Binary::nuInt32($n, fread($f, 4 * $n));
    }

    static public function gitVarInt($str, &$pos = 0) {
        $r = 0;
        $c = 0x80;
        for ($i = 0; $c & 0x80; $i += 7)
        {
            $c = ord($str{$pos++});
            $r |= (($c & 0x7F) << $i);
        }
        return $r;
    }
}