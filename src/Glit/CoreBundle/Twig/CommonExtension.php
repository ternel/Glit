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
            'relativeDate'  => new \Twig_Filter_Method($this, 'relativeDate'),
            'localeDate'    => new \Twig_Filter_Method($this, 'localeDate'),

            'substr'        => new \Twig_Filter_Method($this, 'substr'),

            'debug'         => new \Twig_Filter_Method($this, 'debug'),
            'type'          => new \Twig_Filter_Method($this, 'type'),
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
            return 'today';
        }
        else if ($reldays >= 1 && $reldays < 2) {
            return 'tomorrow';
        }
        else if ($reldays >= -1 && $reldays < 0) {
            return 'yesterday';
        }

        if (abs($reldays) < 7) {
            if ($reldays > 0) {
                $reldays = floor($reldays);

                return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
            }
            else {
                $reldays = abs(floor($reldays));

                return $reldays . ' day' . ($reldays != 1 ? 's' : '') . ' ago';
            }
        }

        if (abs($reldays) < 182) {
            return date('l, j F', $time ? $time : time());
        }
        else {
            return date('l, j F, Y', $time ? $time : time());
        }
    }

    public function localeDate($date, $dateType = 'medium', $timeType = 'none') {
        $values       = array(
            'none'   => \IntlDateFormatter::NONE,
            'short'  => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long'   => \IntlDateFormatter::LONG,
            'full'   => \IntlDateFormatter::FULL,
        );
        $dateFormater = \IntlDateFormatter::create(
            \Locale::getDefault(),
            isset($values[$dateType]) ? $values[$dateType] : $dateType,
            isset($values[$timeType]) ? $values[$timeType] : $timeType,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );
        return $dateFormater->format($date->getTimestamp());
    }

    public function debug($data) {
        return '<pre>' . print_r($data, true) . '</pre>';
    }

    public function type($data) {
        if (is_object($data)) {
            return get_class($data);
        }
        else {
            return gettype($data);
        }
    }

    public function substr($text, $start = 0, $length = null) {
        var_dump($text);
        var_dump($start);
        var_dump($length);
        die('tutu');
        return substr($text, $start, $length);
    }
}