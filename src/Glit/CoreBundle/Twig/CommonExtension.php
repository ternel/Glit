<?php
namespace Glit\CoreBundle\Twig;

class CommonExtension extends \Twig_Extension {

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'glit_core_common';
    }

    public function getFilters() {
        return array(
            'relativeDate' => new \Twig_Filter_Method($this, 'relativeDate')
        );
    }

    /**
     * Create relative from today text representation of a date
     * @param $time
     * @return string
     * TODO : internationalize method
     */
    public function relativeDate($time) {
        $today = strtotime(date('M j, Y'));

        if ($time instanceof \DateTime) {
            $time = $time->getTimestamp();
        }

        $reldays = ($time - $today) / 86400;

        if ($reldays >= 0 && $reldays < 1) {
            return 'Today';
        }
        else if ($reldays >= 1 && $reldays < 2) {
            return 'Tomorrow';
        }
        else if ($reldays >= -1 && $reldays < 0) {
            return 'Yesterday';
        }

        if (abs($reldays) < 7) {
            if ($reldays > 0) {
                $reldays = floor($reldays);

                return 'In ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
            }
            else {
                $reldays = abs(floor($reldays));

                return $reldays . ' day' . ($reldays != 1 ? 's' : '') . ' ago';
            }
        }

        if (abs($reldays) < 182) {
            return date('l, j F', $time ? $time : time());
        } else {
            return date('l, j F, Y', $time ? $time : time());
        }

    }
}