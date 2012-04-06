<?php
namespace Glit\GitoliteBundle\Git;

abstract class PathObject extends GitObject {

    protected
        $mode = null; // the mode of this object

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        if ($this->isReadOnly()) {
            throw new \Exception('cannot set mode on a locked object');
        }
        $this->mode = $mode;
    }

    /**
     * Constructor, sets mode of this object
     *
     * @return void
     * @author The Young Shepherd
     **/
    public function __construct(Repository $git, $sha = null, $mode = null) {
        if (!is_null($mode)) {
            $this->mode = $mode;
        }
        parent::__construct($git, $sha);
    }

    /**
     * Gets all commits in which this object changed
     *
     * @param $commitTip Commit The commit from where to start searching
     * @return array of GitCommit
     */
    public function getHistory(Commit $commitTip) {
        $r       = array();
        $commits = $commitTip->getHistory();
        $path    = $commitTip->getPath($this);
        $last    = null;
        foreach ($commits as $commit)
        {
            $sha = (string)$commit[$path];
            foreach ($commit->parents as $parent)
            {
                if ($sha !== (string)$parent[$path]) {
                    $r[] = $commit;
                    break;
                }
            }
        }
        return $r;
    }

    /**
     * getCommitForLastModification returns the last commit where this object is modified
     *
     * @return Commit
     **/
    public function getCommitForLastModification($from) {
        /** @var $commit Commit */
        $commit = $from; //$this->git->getCommitObject($from);
        $path   = $this->getPath($commit);

        $commits  = $commit->getHistory();
        $commits  = array_reverse($commits);
        $r        = NULL;
        $lastblob = $this->getName();
        foreach ($commits as $commit)
        {
            $blobname = $commit[$path];
            if ($blobname != $lastblob) {
                break;
            }
            $r = $commit->committer->time;
        }
        assert($r !== NULL); /* something is seriously wrong if this happens */
        return $r;
    }
}
