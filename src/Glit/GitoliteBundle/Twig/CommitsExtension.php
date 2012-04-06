<?php
namespace Glit\GitoliteBundle\Twig;

class CommitsExtension extends \Twig_Extension {

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'glit_gitolite_commits';
    }

    public function getFilters() {
        return array(
            'groupByDate' => new \Twig_Filter_Method($this, 'groupByDate'),
        );
    }

    public function groupByDate($commits) {
        $timeline = array();
        $i        = 0;

        $previous = null;
        foreach ($commits as $commit) {
            /** @var $commit \Glit\GitoliteBundle\Git\Commit */
            if (!isset($previous) || date_diff($previous, $commit->getDate())->d !== 0) {
                $i++;
                $previous = $commit->getDate();

                if (!isset($history[$i])) {
                    $timeline[$i] = array(
                        'date'    => $previous,
                        'commits' => array()
                    );
                }
            }

            $timeline[$i]['commits'][] = $commit;
        }

        return $timeline;
    }

}