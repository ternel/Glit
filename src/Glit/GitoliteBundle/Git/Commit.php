<?php
namespace Glit\GitoliteBundle\Git;

use Glit\GitoliteBundle\Utils\SHA;

class Commit extends GitObject {

    /**
     * @brief (string) The tree referenced by this commit, as binary sha1
     * string.
     */
    protected $tree;

    /**
     * @brief (array of string) Parent commits of this commit, as binary sha1
     * strings.
     */
    protected $parents;

    /**
     * @var CommitStamp The author of this commit.
     */
    protected $author;

    /**
     * @var CommitStamp The committer of this commit.
     */
    protected $committer;

    /**
     * @var string Commit summary, i.e. the first line of the commit message.
     */
    protected $summary;

    /**
     * @var string Everything after the first line of the commit message.
     */
    protected $detail;

    protected $history;

    public function getAuthor() {
        return $this->author;
    }

    public function getCommiter() {
        return $this->committer;
    }

    public function getSummary() {
        return $this->summary;
    }

    public function getDetail() {
        return $this->detail;
    }

    public function getDate() {
        return $this->author->time;
    }

    public function getHexIdentifier() {
        return substr(SHA::sha1_hex($this->getName()), 0, 10);
    }

    public function getHexName() {
        return SHA::sha1_hex($this->getName());
    }

    public function __construct(Repository $repo) {
        parent::__construct($repo, GitObject::OBJ_COMMIT);

    }

    public function _unserialize($data) {
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

        $this->tree    = SHA::sha1_bin($meta['tree'][0]);
        $this->parents = array_map('Glit\GitoliteBundle\Utils\SHA::sha1_bin', $meta['parent']);
        $this->author  = new CommitStamp();
        $this->author->unserialize($meta['author'][0]);
        $this->committer = new CommitStamp();
        $this->committer->unserialize($meta['committer'][0]);

        $this->summary = array_shift($lines);
        $this->detail  = implode("\n", $lines);

        $this->history = NULL;
    }

    public function _serialize() {
        $s = '';
        $s .= sprintf("tree %s\n", SHA::sha1_hex($this->tree));
        foreach ($this->parents as $parent)
        {
            $s .= sprintf("parent %s\n", SHA::sha1_hex($parent));
        }
        $s .= sprintf("author %s\n", $this->author->serialize());
        $s .= sprintf("committer %s\n", $this->committer->serialize());
        $s .= "\n" . $this->summary . "\n" . $this->detail;
        return $s;
    }

    /**
     * @brief Get commit history in topological order.
     *
     * @returns (array of GitCommit)
     */
    public function getHistory() {
        if ($this->history) {
            return $this->history;
        }

        /* count incoming edges */
        $inc = array();

        $queue = array($this);
        while (($commit = array_shift($queue)) !== NULL)
        {
            foreach ($commit->parents as $parent)
            {
                if (!isset($inc[$parent])) {
                    $inc[$parent] = 1;

                    $queue[] = $this->repo->getObject($parent);
                }
                else
                {
                    $inc[$parent]++;
                }
            }
        }

        $queue = array($this);
        $r     = array();
        while (($commit = array_pop($queue)) !== NULL)
        {
            array_unshift($r, $commit);
            foreach ($commit->parents as $parent)
            {
                if (--$inc[$parent] == 0) {
                    $queue[] = $this->repo->getObject($parent);
                }
            }
        }

        $this->history = $r;
        return $r;
    }

    /**
     * Return the current commit tree
     * @return Tree
     */
    public function getTree() {
        return $this->repo->getObject($this->tree);
    }

    /**
     * @copybrief Tree::find()
     *
     * This is a convenience function calling GitTree::find() on the commit's
     * tree.
     *
     * @copydetails Tree::find()
     */
    public function find($path) {
        return $this->getTree()->find($path);
    }

    static public function treeDiff(Commit $a, Commit $b) {
        return Tree::treeDiff($a ? $a->getTree() : NULL, $b ? $b->getTree() : NULL);
    }
}