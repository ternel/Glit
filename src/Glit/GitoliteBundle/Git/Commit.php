<?php
namespace Glit\GitoliteBundle\Git;

/**
 * @property $tree
 * @property $parents
 * @property $author
 * @property $committer
 * @property $summary
 * @property $detail
 */
class Commit extends GitObject {

    protected
        $data = array(
        'tree'      => null, // (GitTree) The tree referenced by this commit
        'parents'   => array(), // (array of GitCommit) Parent commits of this commit
        'author'    => null, // (CommitStamp) The author of this commit
        'committer' => null, // (CommitStamp) The committer of this commit
        'summary'   => "", // (string) Commit summary, i.e. the first line of the commit message
        'detail'    => "" // (string) Everything after the first line of the commit message
    ),
        $commitHistory = null; // cache for history of this object

    public function __construct($git, $sha = null) {

        if ($git instanceof Commit || is_array($git)) {
            //assume a new commit to be based on a previous commit
            //$git represents the parents of the new object
            $parents = is_array($git) ? $git : array($git);
            /** @var $firstParent Commit */
            $firstParent = $parents[0];
            parent::__construct($firstParent->getRepository());
            $this->parents = $parents;
            $this->tree    = clone $firstParent->tree;
        }
        else
        {
            parent::__construct($git, $sha);
        }
    }

    public function getTypeName() {
        return 'commit';
    }

    public function unserialize($data) {
        $lines = explode("\n", $data);
        unset($data);

        $meta = array('parent' => array());
        while (($line = array_shift($lines)) != '')
        {
            $parts = explode(' ', $line, 2);
            if (!isset($meta[$parts[0]])) {
                $meta[$parts[0]] = array($parts[1]);
            }
            else
            {
                $meta[$parts[0]][] = $parts[1];
            }
        }

        //$this->data['tree'] = new GitTree($this->git, $meta['tree'][0]);

        $parents = array();
        foreach ($meta['parent'] as $sha)
        {
            $parents[] = new Commit($this->git, $sha);
        }
        $this->data['parents'] = $parents;

        $this->data['author'] = new CommitStamp();
        $this->data['author']->unserialize($meta['author'][0]);

        $this->data['committer'] = new CommitStamp();
        $this->data['committer']->unserialize($meta['committer'][0]);

        $this->data['summary'] = array_shift($lines);
        $this->data['detail']  = implode("\n", $lines);
    }

    public function setMessage($message) {
        $message       = explode("\n", $message, 2);
        $this->summary = isset($message[0]) ? $message[0] : "";
        $this->detail  = isset($message[1]) ? $message[1] : "";
    }

    protected function _serialize() {
        $s = '';
        //$s = sprintf("tree %s\n", $this->tree->getSha()->hex());

        foreach ($this->parents as $parent)
        {
            $s .= sprintf("parent %s\n", $parent->getSha()->hex());
        }

        $s .= sprintf("author %s\n", $this->data['author']->serialize());
        $s .= sprintf("committer %s\n", $this->data['committer']->serialize());

        $s .= "\n";

        $s .= $this->summary . "\n" . $this->detail;

        return $s;
    }

    /**
     * @brief Get commit history in topological order.
     *
     * @returns (array of GitCommit)
     */
    public function getHistory() {
        if (is_null($this->commitHistory)) {
            /* count incoming edges */
            $inc = array();

            $queue = array($this);
            while (($commit = array_shift($queue)) !== NULL)
            {
                foreach ($commit->parents as $parent)
                {
                    if (!isset($inc[(string)$parent])) {
                        $inc[(string)$parent] = 1;
                        $queue[]              = $parent;
                    }
                    else
                    {
                        $inc[(string)$parent]++;
                    }
                }
            }

            $queue               = array($this);
            $this->commitHistory = array();
            while (($commit = array_pop($queue)) !== NULL)
            {
                array_unshift($this->commitHistory, $commit);
                foreach ($commit->parents as $parent)
                {
                    if (--$inc[(string)$parent] == 0) {
                        $queue[] = $parent;
                    }
                }
            }
        }

        return $this->commitHistory;
    }
}