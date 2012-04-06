<?php
namespace Glit\GitoliteBundle\Git;

class TreeNode {

    protected $repo;

    protected $mode;

    protected $name;

    protected $isDir;

    protected $isSubModule;

    protected $objectHead;

    public function __construct(Repository $repo) {
        $this->repo = $repo;
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

    public function getObjectHead() {
        return $this->objectHead;
    }

    public function getObject() {
        return $this->repo->getObject($this->objectHead);
    }
}