<?php
namespace Glit\GitoliteBundle\Git;

use \Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Glit\GitoliteBundle\Utils\SHA;
use Glit\GitoliteBundle\Utils\Binary;

class Repository {

    /** @var \Glit\CoreBundle\Utils\SystemPathObject */
    private $path;

    /** @var \Glit\CoreBundle\Utils\SystemPathObject */
    private $internalPath;

    /** @var null|\Symfony\Component\HttpKernel\Log\LoggerInterface */
    private $logger;

    // Internals
    private $cache = array();
    private $packs = array();
    private $branchCache = array();

    public static function cloneRepository($remote, $localPath, LoggerInterface $logger = null) {
        self::staticExecProcessAsGlit($localPath, sprintf('git clone %s %s', $remote, $localPath, $logger));
        return new self($localPath, $logger);
    }

    public function __construct($path, LoggerInterface $logger = null) {
        $this->logger = $logger;

        $path = new \Glit\CoreBundle\Utils\SystemPathObject($path);

        // We are in .git or in a repository containing a .git
        if ($path->buildSubPath('HEAD')->exists()) {
            $this->internalPath = new \Glit\CoreBundle\Utils\SystemPathObject($path);
            $this->path         = null;
        }
        else {
            $this->path         = $path;
            $this->internalPath = $path->buildSubPath('.git');

            // Validate we are in a git repository
            if (!$this->internalPath->exists()) {
                throw new \Exception(sprintf('"%s" or any of its parent is a git directory'));
            }
        }

        $this->initialize();
    }

    /**
     * Initialize repository
     */
    private function initialize() {
        // Load packs
        $dh = opendir($this->internalPath->buildSubPath(array('objects', 'pack')));
        while (($entry = readdir($dh)) !== false) {
            if (preg_match('#^pack-([0-9a-fA-F]{40})\.idx$#', $entry, $m)) {
                $this->packs[] = new SHA($m[1]);
            }
        }

        // Load branches
        $dh = opendir($this->internalPath->buildSubPath(array('refs', 'heads')));
        while (($entry = readdir($dh)) !== false) {
            if (!in_array($entry, array('.', '..', 'HEAD'))) {
                $this->branchCache[$entry] = new Branch($this, $entry, $this->cache);
            }
        }
    }

    /**
     * @return \Glit\CoreBundle\Utils\SystemPathObject
     */
    public function getInternalPath() {
        return $this->internalPath;
    }

    /**
     * Check if the current repository contain only internals git (like a repository on the gitolite server)
     * @return bool
     */
    public function IsOnlyInternal() {
        return $this->path !== null;
    }

    /**
     * @param $branchName
     * @return Branch
     */
    public function getBranch($branchName) {
        if (!isset($this->branchCache[$branchName])) {
            $this->branchCache[$branchName] = new Branch($this, $branchName, $this->cache);
        }
        return $this->branchCache[$branchName];
    }

    public function getBranches() {
        return $this->branchCache;
    }

    public function getBranchNames() {
        return array_keys($this->branchCache);
    }

    public function isNew() {
        return count($this->branchCache) == 0;
    }

    /**
     * @brief Unpack an object from a pack.
     *
     * @param $pack (resource) open .pack file
     * @param $object_offset (integer) offset of the object in the pack
     * @return (array) an array consisting of the object type name (string) and the
     * binary representation of the object (string)
     */
    protected function unpackObject($pack, $object_offset) {
        fseek($pack, $object_offset);

        /* read object header */
        $c    = ord(fgetc($pack));
        $type = ($c >> 4) & 0x07;
        $size = $c & 0x0F;
        for ($i = 4; $c & 0x80; $i += 7)
        {
            $c = ord(fgetc($pack));
            $size |= ($c << $i);
        }

        /* compare sha1_file.c:1608 unpack_entry */
        if ($type == GitObject::OBJ_COMMIT || $type == GitObject::OBJ_TREE || $type == GitObject::OBJ_BLOB || $type == GitObject::OBJ_TAG) {
            /*
            * We don't know the actual size of the compressed
            * data, so we'll assume it's less than
            * $object_size+512.
            *
            * FIXME use PHP stream filter API as soon as it behaves
            * consistently
            */
            $data = gzuncompress(fread($pack, $size + 512), $size);
        }
        else if ($type == GitObject::OBJ_OFS_DELTA) {
            /* 20 = maximum varint length for offset */
            $buf = fread($pack, $size + 512 + 20);

            /*
            * contrary to varints in other places, this one is big endian
            * (and 1 is added each turn)
            * see sha1_file.c (get_delta_base)
            */
            $pos    = 0;
            $offset = -1;
            do
            {
                $offset++;
                $c      = ord($buf{$pos++});
                $offset = ($offset << 7) + ($c & 0x7F);
            }
            while ($c & 0x80);

            $delta = gzuncompress(substr($buf, $pos), $size);
            unset($buf);

            $base_offset = $object_offset - $offset;
            assert($base_offset >= 0);
            list($type, $base) = $this->unpackObject($pack, $base_offset);

            $data = $this->applyDelta($delta, $base);
        }
        else if ($type == GitObject::OBJ_REF_DELTA) {
            //TODO the following line is untested
            $base_name = new SHA(fread($pack, 20));
            list($type, $base) = $this->getRawObject($base_name);

            // $size is the length of the uncompressed delta
            $delta = gzuncompress(fread($pack, $size + 512), $size);

            $data = $this->applyDelta($delta, $base);
        }
        else
        {
            throw new \Exception(sprintf('object of unknown type %d', $type));
        }

        if (is_numeric($type)) {
            $type = GitObject::staticGetTypeName($type);
        }
        return array($type, $data);
    }

    /**
     * @brief Apply the git delta $delta to the byte sequence $base.
     *
     * @param $delta (string) the delta to apply
     * @param $base (string) the sequence to patch
     * @return (string) the patched byte sequence
     */
    protected function applyDelta($delta, $base) {
        $pos = 0;

        $base_size   = Binary::gitVarInt($delta, $pos);
        $result_size = Binary::gitVarInt($delta, $pos);

        $r = '';
        while ($pos < strlen($delta))
        {
            $opcode = ord($delta{$pos++});
            if ($opcode & 0x80) {
                /* copy a part of $base */
                $off = 0;
                if ($opcode & 0x01) {
                    $off = ord($delta{$pos++});
                }
                if ($opcode & 0x02) {
                    $off |= ord($delta{$pos++}) << 8;
                }
                if ($opcode & 0x04) {
                    $off |= ord($delta{$pos++}) << 16;
                }
                if ($opcode & 0x08) {
                    $off |= ord($delta{$pos++}) << 24;
                }
                $len = 0;
                if ($opcode & 0x10) {
                    $len = ord($delta{$pos++});
                }
                if ($opcode & 0x20) {
                    $len |= ord($delta{$pos++}) << 8;
                }
                if ($opcode & 0x40) {
                    $len |= ord($delta{$pos++}) << 16;
                }
                $r .= substr($base, $off, $len);
            }
            else
            {
                /* take the next $opcode bytes as they are */
                $r .= substr($delta, $pos, $opcode);
                $pos += $opcode;
            }
        }
        return $r;
    }

    /**
     * @brief Tries to find $object_name in the fanout table in $f at $offset.
     *
     * @return array The range where the object can be located (first possible
     * location and past-the-end location)
     */
    protected function readFanout($f, $object_name, $offset) {
        $object_name = (string)$object_name;
        if ($object_name{0} == "\x00") {
            $cur = 0;
            fseek($f, $offset);
            $after = Binary::fuInt32($f);
        }
        else
        {
            fseek($f, $offset + (ord($object_name{0}) - 1) * 4);
            $cur   = Binary::fuInt32($f);
            $after = Binary::fuInt32($f);
        }

        return array($cur, $after);
    }

    /**
     * @brief Try to find an object in a pack.
     *
     * @param $object_name (string) name of the object (binary SHA1)
     * @return (array) an array consisting of the name of the pack (SHA) and
     * the byte offset inside it, or NULL if not found
     */
    protected function findPackedObject(SHA $objectSha) {
        foreach ($this->packs as $packSha)
        {
            $index = fopen($this->internalPath->buildSubPath(array('objects', 'pack', sprintf('pack-%s.pack', $packSha->hex()))), 'rb');
            flock($index, LOCK_SH);

            /* check version */
            $magic = fread($index, 4);
            if ($magic != "\xFFtOc") {
                /* version 1 */
                /* read corresponding fanout entry */
                list($cur, $after) = $this->readFanout($index, $objectSha, 0);

                $n = $after - $cur;
                if ($n == 0) {
                    continue;
                }

                /*
                * TODO: do a binary search in [$offset, $offset+24*$n)
                */
                fseek($index, 4 * 256 + 24 * $cur);
                for ($i = 0; $i < $n; $i++)
                {
                    $off  = Binary::fuInt32($index);
                    $name = fread($index, 20);
                    if ($name === (string)$objectSha) {
                        /* we found the object */
                        fclose($index);
                        return array($packSha, $off);
                    }
                }
            }
            else
            {
                /* version 2+ */
                $version = Binary::fuInt32($index);
                if ($version == 2) {
                    list($cur, $after) = $this->readFanout($index, $objectSha, 8);

                    if ($cur == $after) {
                        continue;
                    }

                    fseek($index, 8 + 4 * 255);
                    $total_objects = Binary::fuInt32($index);

                    /* look up sha1 */
                    fseek($index, 8 + 4 * 256 + 20 * $cur);
                    for ($i = $cur; $i < $after; $i++)
                    {
                        $name = fread($index, 20);
                        if ($name === (string)$objectSha) {
                            break;
                        }
                    }
                    if ($i == $after) {
                        continue;
                    }

                    fseek($index, 8 + 4 * 256 + 24 * $total_objects + 4 * $i);
                    $off = Binary::fuInt32($index);
                    if ($off & 0x80000000) {
                        /* packfile > 2 GB. Gee, you really want to handle this
                        * much data with PHP?
                        */
                        throw new Exception('64-bit packfiles offsets not implemented');
                    }

                    fclose($index);
                    return array($packSha, $off);
                }
                else
                {
                    throw new Exception('unsupported pack index format');
                }
            }
            fclose($index);
        }
        /* not found */
        return NULL;
    }

    /**
     * @brief Fetch an object in its binary representation by name.
     *
     * Throws an exception if the object cannot be found.
     *
     * @param $object_name (string) name of the object (binary SHA1)
     * @return (array) an array consisting of the object type name (string) and the
     * binary representation of the object (string)
     */
    public function getRawObject(SHA $sha) {
        static $cache = array();
        /* FIXME allow limiting the cache to a certain size */

        if (!isset($cache[(string)$sha])) {
            $path = $this->internalPath->buildSubPath(array(
                                                           'objects',
                                                           substr($sha->hex(), 0, 2),
                                                           substr($sha->hex(), 2)
                                                      ));

            if ($path->exists()) {
                list($hdr, $object_data) = explode("\0", gzuncompress($this->readFile($path)), 2);
                $object_size = null;
                sscanf($hdr, "%s %d", $type, $object_size);

                $cache[(string)$sha] = array($type, $object_data);
            }
            else if ($x = $this->findPackedObject($sha)) {
                list($pack_sha, $object_offset) = $x;
                /** @var $pack_sha SHA  */

                $pack = fopen($this->internalPath->buildSubPath(array('objects', 'pack', sprintf('pack-%s.pack', $pack_sha->hex()))), 'rb');
                flock($pack, LOCK_SH);

                /* check magic and version */
                $magic   = fread($pack, 4);
                $version = Binary::fuInt32($pack);
                if ($magic != 'PACK' || $version != 2) {
                    throw new \Exception('unsupported pack format');
                }

                $cache[(string)$sha] = $this->unpackObject($pack, $object_offset);
                fclose($pack);
            }
            else
            {
                throw new \Exception(sprintf('object not found: %s', $sha->hex()));
            }
        }

        return $cache[(string)$sha];
    }

    /**
     * Commit some files
     * @param $file string|array Files to commit
     * @param $message string Commit message
     */
    public function commitFile($file, $message) {
        if (!is_array($file)) {
            $file = array($file);
        }

        foreach ($file as $f) {
            $this->execProcessAsGlit(sprintf('git add %s', $f));
        }

        $this->execProcessAsGlit(sprintf('git commit -m "%s"', $message));

    }

    /**
     * Push repository
     */
    public function push() {
        $this->execProcessAsGlit('git push');
    }

    /**
     * Push repository
     */
    public function pull() {
        $this->execProcessAsGlit('git pull');
    }

    // File Manipulation
    // ---------------------------------

    /**
     * @param $file
     * @param $data
     */
    public function saveFile($file, $data) {
        $this->execProcessAsGlit(sprintf('echo "%s" > %s', $data, $file));
    }

    /**
     * @param $file
     */
    public function deleteFile($file) {
        $this->execProcessAsGlit('rm %s' . $file);
    }

    /**
     * Read file
     * @param $file
     * @return string
     */
    public function readFile($file) {
        return $this->execProcessAsGlit('cat ' . $file)->getOutput();
    }

    /**
     * List files in a directory
     * @param $directory
     * @return array
     */
    public function listFiles($directory) {
        return array_merge(array_filter(
            explode("\n", $this->execProcessAsGlit(sprintf('ls %s | cat', $directory))->getOutput()),
            'strlen'
        ));
    }

    // Process Execution
    // ---------------------------------

    /**
     * @param $command
     * @return \Symfony\Component\Process\Process
     * @throws RuntimeException
     */
    protected function execProcessAsGlit($command) {
        return self::staticExecProcessAsGlit($this->path, $command, $this->logger);
    }

    /**
     * @param $command
     * @return \Symfony\Component\Process\Process
     * @throws RuntimeException
     */
    protected static function staticExecProcessAsGlit($path, $command, LoggerInterface $logger = null) {
        $command = sprintf('cd %s; %s', $path, $command);

        if ($logger != null) {
            $logger->addDebug('Execute : ' . $command);
        }

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        if ($logger != null) {
            $logger->addDebug('Result : ' . $process->getOutput());
        }

        return $process;
    }
}