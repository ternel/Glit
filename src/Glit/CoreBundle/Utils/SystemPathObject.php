<?php
namespace Glit\CoreBundle\Utils;

class SystemPathObject {

    private $pathArray;

    public function __construct($path) {
        $this->pathArray = array();

        $stringObject = new StringObject($path);
        $parts        = $stringObject->explode(DS);

        if ($stringObject->startsWith(DS)) {
            $parts[0] = DS;
        }
        $this->pathArray = array_filter($parts, 'strlen');
    }

    public function join($items) {
        if (!is_array($items)) {
            $items = explode(DS, $items);
        }

        $this->pathArray = array_merge($this->pathArray, array_filter($items, 'strlen'));
    }

    /**
     * @param $items
     * @return SystemPathObject
     */
    public function buildSubPath($items) {
        $new = clone $this;
        $new->join($items);
        return $new;
    }

    public function exists() {
        return file_exists($this);
    }

    public function __toString() {
        $path = '';
        $max  = count($this->pathArray);
        for ($i = 0; $i < $max; $i++) {
            $part = $this->pathArray[$i];
            $path .= $part . ($part != DS && $i < $max - 1 ? DS : '');
        }

        return $path;
    }

}