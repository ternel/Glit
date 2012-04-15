<?php
namespace Glit\GitoliteBundle\Git;

use Glit\GitoliteBundle\Utils\SHA;
use Glit\CoreBundle\Utils\StringObject;

class TreeNode {

    protected $repo;

    protected $mode;

    protected $name;

    protected $isDir;

    protected $isSubModule;

    protected $objectHead;

    protected $path;

    protected $history;

    public function __construct(Repository $repo, $path) {
        $this->repo        = $repo;
        $this->path        = $path;
        $this->isDir       = false;
        $this->isSubModule = false;
    }

    public function setIsDir($isDir) {
        $this->isDir = $isDir;
    }

    public function getIsDir() {
        return $this->isDir;
    }

    public function setIsSubModule($isSubModule) {
        $this->isSubModule = $isSubModule;
    }

    public function getIsSubModule() {
        return $this->isSubModule;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }

    public function getMode() {
        return $this->mode;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setObjectHead($objectHead) {
        $this->objectHead = $objectHead;
    }

    public function setRepo($repo) {
        $this->repo = $repo;
    }

    public function getObjectHead() {
        return $this->objectHead;
    }

    public function getObject() {
        return $this->repo->getObject($this->objectHead, $this->path);
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function getPath() {
        return $this->path . DS . $this->name;
    }

    /**
     * Get the node object revision history
     * @param Commit $start
     */
    public function getHistory(Commit $start) {
        if (!isset($this->history)) {

            $commitHistory = array_reverse($start->getHistory());
            $this->history = array();
            foreach ($commitHistory as $commit) {
                /** @var $commit Commit */
                /** @var $node TreeNode */
                $node = $commit->getTree()->find($this->getPath());
                if (is_null($node)) {
                    // object not update set
                    continue;
                }

                $diff = $commit->diffWithPreviousCommit();

                foreach (array_keys($diff) as $object) {
                    if (StringObject::staticStartsWith($object, $this->getPath())) {
                        $this->history[] = $commit;
                        break;
                    }
                }
            }
        }

        return $this->history;
    }

    /**
     * @param Commit $start
     * @return Commit
     */
    public function getLastCommit(Commit $start) {
        $history = $this->getHistory($start);

        return $history[0];
    }

    public function getDiff() {

    }

    public function __sleep() {
        return array(
            'mode',
            'name',
            'isDir',
            'isSubModule',
            'objectHead',
            'path'
        );
    }
}