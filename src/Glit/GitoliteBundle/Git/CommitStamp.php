<?php
namespace Glit\GitoliteBundle\Git;

class CommitStamp {

    public $name;
    public $email;
    /** @var \DateTime */
    public $time;
    public $offset;

    public function __construct($name = null, $email = null) {
        $name = trim($name);
        if (empty($name)) {
            $this->name = "Anonymous User";
        }
        else {
            $this->name = $name;
        }

        if (empty($email)) {
            $this->email = "anonymous@" . (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "unknown");
        }
        else {
            $this->email = $email;
        }

        $this->time   = time();
        $this->offset = idate('Z', $this->time);
    }

    public function unserialize($data) {
        assert(preg_match('/^(.+?)\s+<(.+?)>\s+(\d+)\s+([+-]\d{4})$/', $data, $m));

        $this->name  = $m[1];
        $this->email = $m[2];

        $this->time = new \DateTime();
        $this->time->setTimestamp(intval($m[3]));

        $off          = intval($m[4]);
        $this->offset = intval($off / 100) * 3600 + ($off % 100) * 60;
    }

    public function serialize() {
        if ($this->offset % 60) {
            throw new \Exception('cannot serialize sub-minute timezone offset');
        }

        return sprintf('%s <%s> %d %+05d', $this->name, $this->email, $this->time->getTimestamp(), intval($this->offset / 3600) * 100 + intval($this->offset / 60) % 60);
    }
}