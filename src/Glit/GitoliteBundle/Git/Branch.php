<?php
namespace Glit\GitoliteBundle\Git;

use Glit\GitoliteBundle\Utils\SHA;

class Branch {

    const cacheKeyPrefix = 'gitbranch_';

    private $repository;
    private $branchName;
    private $cache;
    private $tipCache;

    public function __construct(Repository $repository, $branchName, &$cache) {
        $this->repository = $repository;
        $this->branchName = $branchName;

        if (isset($cache[self::cacheKeyPrefix . $branchName])) {
            $this->cache =& $cache[self::cacheKeyPrefix . $branchName];
        }
        else {
            $cache[self::cacheKeyPrefix . $branchName] =& $this->cache;
        }
    }

    public function &getCache() {
        return $this->cache;
    }

    public function clearCache($key = null) {
        if (is_null($key)) {
            //clear the cache, don't assign it to an empty array as it might be a pointer inside an external store
            foreach (array_keys($this->cache) as $key) {
                unset($this->cache[$key]);
            }
        }
        else {
            unset($this->cache[(string)$key]);
        }
    }

    public function isDirty() {
        return count($this->cache);
    }

    /**
     * @param bool $noCache
     * @return Commit
     * @throws \Exception
     */
    public function getTip($noCache = false) {
        if (!is_null($this->tipCache) && !$noCache) {
            return $this->tipCache;
        }

        $subpath = 'refs' . DS . 'heads' . DS . $this->branchName;
        $path    = $this->repository->getInternalPath()->buildSubPath($subpath);

        if (file_exists($path)) {
            $this->tipCache = $this->repository->getObject(SHA::sha1_bin(file_get_contents($path)));
            return $this->tipCache;
        }

        $path = $this->repository->getInternalPath()->buildSubPath('packed-refs');
        if (file_exists($path)) {
            $head = NULL;
            $f    = fopen($path, 'rb');
            flock($f, LOCK_SH);
            while ($head === NULL && ($line = fgets($f)) !== FALSE)
            {
                if ($line{0} == '#') {
                    continue;
                }

                $parts = explode(' ', trim($line));
                if (count($parts) == 2 && $parts[1] == $subpath) {
                    $head = SHA::sha1_bin($parts[0]);
                }
            }

            fclose($f);

            if ($head !== NULL) {
                $this->tipCache = $this->repository->getObject($head);
                return $this->tipCache;
            }
        }

        throw new \Exception(sprintf('no such branch: %s', $this->branchName));
    }

    public function getHistory() {
        return $this->getTip()->getHistory();
    }

}