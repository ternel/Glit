<?php
namespace Glit\GitoliteBundle\Git;

class Blob extends GitObject {

    /**
     * @brief The data contained in this blob.
     */
    public $data = NULL;

    public function __construct($repo) {
        parent::__construct($repo, GitObject::OBJ_BLOB);
    }

    protected function _unserialize($data) {
        $this->data = $data;
    }

    protected function _serialize() {
        return $this->data;
    }
}