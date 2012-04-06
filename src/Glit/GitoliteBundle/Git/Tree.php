<?php
namespace Glit\GitoliteBundle\Git;

use Glit\GitoliteBundle\Utils\SHA;

class TreeError extends \Exception {

}

class TreeInvalidPathError extends TreeError {

}

class Tree extends GitObject {

    protected $nodes = array();
    protected $path;

    public function __construct($repo) {
        parent::__construct($repo, GitObject::OBJ_TREE);
    }

    public function getNodes() {
        return $this->nodes;
    }

    protected static function nodecmp(&$a, &$b) {
        return strcmp($a->name, $b->name);
    }

    public function _unserialize($data) {
        $this->nodes = array();
        $start       = 0;
        while ($start < strlen($data))
        {
            $node = new TreeNode($this->repo, $this->path);

            $pos = strpos($data, "\0", $start);
            list($mode, $name) = explode(' ', substr($data, $start, $pos - $start), 2);

            $node->setName($name);
            $node->setMode(intval($mode, 8));
            $node->setIsDir(!!($node->getMode() & 040000));
            $node->setIsSubModule($node->getMode() == 57344);
            $node->setObjectHead(substr($data, $pos + 1, 20));

            $start = $pos + 21;

            $this->nodes[$node->getName()] = $node;
        }
        unset($data);
    }

    public function _serialize() {
        $s = '';
        /* git requires nodes to be sorted */
        uasort($this->nodes, array('GitTree', 'nodecmp'));
        foreach ($this->nodes as $node) {
            /** @var $node TreeNode */
            $s .= sprintf("%s %s\0%s", base_convert($node->getMode(), 10, 8), $node->getName(), $node->getObject());
        }
        return $s;
    }

    /**
     * @brief Find the tree or blob at a certain path.
     *
     * @throws TreeInvalidPathError The path was found to be invalid. This
     * can happen if you are trying to treat a file like a directory (i.e.
     * @em foo/bar where @em foo is a file).
     *
     * @param $path (string) The path to look for, relative to this tree.
     * @returns Tree|Blob The Tree or Blob at the specified path, or NULL if none
     * could be found.
     */
    public function find($path) {
        if (!is_array($path)) {
            $path = explode('/', $path);
        }

        while ($path && !$path[0])
        {
            array_shift($path);
        }
        if (!$path) {
            return $this->getName();
        }

        if (!isset($this->nodes[$path[0]])) {
            return NULL;
        }
        $cur = $this->nodes[$path[0]];

        array_shift($path);
        while ($path && !$path[0])
        {
            array_shift($path);
        }

        if (!$path) {
            return $cur;
        }
        else
        {
            /** @var $cur TreeNode */
            $cur = $this->repo->getObject($cur->getObjectHead());
            /** @var $cur Tree */
            if (!($cur instanceof Tree)) {
                throw new TreeInvalidPathError;
            }
            return $cur->find($path);
        }
    }

    /**
     * @brief Recursively list the contents of a tree.
     *
     * @returns (array mapping string to string) An array where the keys are
     * paths relative to the current tree, and the values are SHA-1 names of
     * the corresponding blobs in binary representation.
     */
    public function listRecursive() {
        $r = array();

        foreach ($this->nodes as $node)
        {
            /** @var $node TreeNode */

            if ($node->getIsDir()) {
                if ($node->getIsSubmodule()) {
                    $r[$node->getName() . ':submodule'] = $node->getObjectHead();
                }
                else
                {
                    /** @var $subtree Tree */
                    $subtree = $this->repo->getObject($node->getObjectHead());
                    foreach ($subtree->listRecursive() as $entry => $blob)
                    {
                        $r['/' . $node->getName() . $entry] = $blob;
                    }
                }
            }
            else
            {
                $r['/' . $node->getName()] = $node->getObjectHead();
            }
        }

        return $r;
    }

    /**
     * @brief Updates a node in this tree.
     *
     * Missing directories in the path will be created automatically.
     *
     * @param $path (string) Path to the node, relative to this tree.
     * @param $mode mixed Git mode to set the node to. 0 if the node shall be
     * cleared, i.e. the tree or blob shall be removed from this path.
     * @param $object (string) Binary SHA-1 hash of the object that shall be
     * placed at the given path.
     *
     * @returns (array of GitObject) An array of GitObject%s that were newly
     * created while updating the specified node. Those need to be written to
     * the repository together with the modified tree.
     */
    public function updateNode($path, $mode, $object) {
        if (!is_array($path)) {
            $path = explode('/', $path);
        }
        $name = array_shift($path);
        if (count($path) == 0) {
            /* create leaf node */
            if ($mode) {
                $node = new TreeNode($this->repo, $this->path);
                $node->setMode($mode);
                $node->setName($name);
                $node->setObjectHead($object);
                $node->setIsDir(!!($mode & 040000));

                $this->nodes[$node->getName()] = $node;
            }
            else
            {
                unset($this->nodes[$name]);
            }

            return array();
        }
        else
        {
            /* descend one level */
            if (isset($this->nodes[$name])) {
                $node = $this->nodes[$name];
                if (!$node->getIsDir()) {
                    throw new TreeInvalidPathError;
                }
                $subtree = clone $this->repo->getObject($node->getObjectHead(), $this->path);
            }
            else
            {
                /* create new tree */
                $subtree = new Tree($this->repo);

                $node = new TreeNode($this->repo, $this->path);
                $node->setMode(040000);
                $node->setName($name);
                $node->setIsDir(TRUE);

                $this->nodes[$node->getName()] = $node;
            }
            $pending = $subtree->updateNode($path, $mode, $object);

            $subtree->rehash();
            $node->setObjectHead($subtree->getName());

            $pending[] = $subtree;
            return $pending;
        }
    }

    const TREEDIFF_A = 0x01;
    const TREEDIFF_B = 0x02;

    const TREEDIFF_REMOVED = self::TREEDIFF_A;
    const TREEDIFF_ADDED   = self::TREEDIFF_B;
    const TREEDIFF_CHANGED = 0x03;

    static public function treeDiff(Tree $a_tree, $b_tree) {
        $a_blobs = $a_tree ? $a_tree->listRecursive() : array();
        $b_blobs = $b_tree ? $b_tree->listRecursive() : array();

        $a_files = array_keys($a_blobs);
        $b_files = array_keys($b_blobs);

        $changes = array();

        sort($a_files);
        sort($b_files);
        $a = $b = 0;
        while ($a < count($a_files) || $b < count($b_files))
        {
            if ($a < count($a_files) && $b < count($b_files)) {
                $cmp = strcmp($a_files[$a], $b_files[$b]);
            }
            else
            {
                $cmp = 0;
            }
            if ($b >= count($b_files) || $cmp < 0) {
                $changes[$a_files[$a]] = self::TREEDIFF_REMOVED;
                $a++;
            }
            else if ($a >= count($a_files) || $cmp > 0) {
                $changes[$b_files[$b]] = self::TREEDIFF_ADDED;
                $b++;
            }
            else
            {
                if ($a_blobs[$a_files[$a]] != $b_blobs[$b_files[$b]]) {
                    $changes[$a_files[$a]] = self::TREEDIFF_CHANGED;
                }

                $a++;
                $b++;
            }
        }

        return $changes;
    }

    /**
     * returns the relative path of an object in this Tree
     *
     * @param $obj TreeNode The object to find the path for
     * @return string or null if not found
     **/
    public function getPath(TreeNode $obj) {
        $nodes = $this->listRecursive();
        $path  = array_search($obj, $nodes, true);
        return false === $path ? null : $path;
    }

    public function setPath($path) {
        $this->path = $path;
    }
}