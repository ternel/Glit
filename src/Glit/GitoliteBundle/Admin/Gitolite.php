<?php
namespace Glit\GitoliteBundle\Admin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Glit\GitoliteBundle\Git\Repository;
use Glit\CoreBundle\Utils\SystemPathObject;

class Gitolite {

    /** @var LoggerInterface */
    private $logger;
    /** @var ContainerInterface */
    private $container;
    private $adminUri = 'git@localhost:gitolite-admin.git';
    private $localDir;
    private $repoDir;
    private $gitoliteRepoDir;

    /** @var Repository */
    private $gitRepository;

    private $sshKeys;
    private $repositories;

    public function __construct(LoggerInterface $logger, ContainerInterface $container) {
        $this->logger    = $logger;
        $this->container = $container;

        $this->localDir        = $this->container->getParameter('glit.gitolite.tmp_admin');
        $this->gitoliteRepoDir = new SystemPathObject($this->container->getParameter('glit.gitolite.repositories'));
        $this->repoDir         = $this->gitoliteRepoDir->buildSubPath('gitolite-admin.git');

        $this->initialize();
    }

    protected function initialize() {
        if (!file_exists($this->localDir)) {
            $this->gitRepository = Repository::cloneRepository($this->adminUri, $this->localDir, $this->logger);
        }
        else {
            $this->gitRepository = new Repository($this->localDir, $this->logger, $this->repoDir);
        }

        // Load keys and repository
        $this->loadUserKeyList();
        $this->loadRepositories();
    }

    private function loadUserKeyList() {
        $sourceKeys = $this->gitRepository->listFiles('keydir' . DS);

        foreach ($sourceKeys as $key) {
            list($keyName, $extension) = explode('.', $key);
            $this->sshKeys[$keyName] = $key;
        }
    }

    private function loadRepositories() {
        $lines = explode("\n", $this->gitRepository->readFile('conf' . DS . 'gitolite.conf'));

        $tempRepository = null;
        $cleaned        = false;

        foreach ($lines as $line) {
            $temp = array_merge(array_filter(explode(" ", $line), 'strlen'));

            if (count($temp) <= 0) {
                continue;
            }

            if ($temp[0] == 'repo') {
                if (null !== $tempRepository) {
                    $this->repositories[$tempRepository['name']] = $tempRepository;
                }

                $tempRepository = array(
                    'name'  => $temp[1],
                    'users' => array()
                );
            }
            else {
                $users = array_merge(array_filter(explode(' ', $temp[2])));

                foreach ($users as $user) {
                    if (!isset($this->sshKeys[$user])) {
                        $cleaned = true;
                        continue;
                    }
                    $tempRepository['users'][$user] = $temp[0];
                }
            }
        }

        if ($cleaned) {
            $this->writeRepositoriesConf("Cleaning repository configuration");
        }

        $this->repositories[$tempRepository['name']] = $tempRepository;
    }

    private function writeRepositoriesConf($commitMessage = 'Save gitolite repositories confifuration.') {
        $conf = '';

        foreach ($this->repositories as $repository) {
            // Add line break on all lines except first one
            if (!empty($conf)) {
                $conf .= "\n";
            }

            $conf .= sprintf("repo    %s\n", $repository['name']);

            foreach ($repository['users'] as $name => $right) {
                $conf .= sprintf("        %s = %s\n", $right, $name);
            }
        }

        $confFile = 'conf' . DS . 'gitolite.conf';
        $this->gitRepository->saveFile($confFile, $conf);
        if ($commitMessage == null) {
            return $confFile;
        }
        $this->gitRepository->commitFile($confFile, $commitMessage);
        $this->gitRepository->push();
    }

    /**
     * @param $path
     */
    public function getRepository($path) {
        $cachePath = new SystemPathObject($this->container->getParameter('kernel.cache_dir'));
        $cachePath->join(array(
                              'gitolite',
                              str_replace(DS, '_', $path)
                         ));
        return new Repository($this->gitoliteRepoDir->buildSubPath($path), $this->logger, null, $cachePath);
    }

    /**
     * Add or update user key
     * @param $name
     * @param $sshKey
     */
    public function saveUserKey($name, $sshKey) {
        $file = 'keydir' . DS . $name . '.pub';

        $this->gitRepository->saveFile($file, $sshKey);
        $this->sshKeys[$name] = $name . '.pub';

        $this->gitRepository->commitFile($file, 'define ssh key named ' . $name);
        $this->gitRepository->push();
    }

    /**
     * Remove user key
     * @param $name
     */
    public function deleteUserKey($name) {
        $file = 'keydir' . DS . $name . '.pub';

        unset ($this->sshKeys[$name]);
        // Remove key from repositories
        foreach ($this->repositories as $key => $repository) {
            if (isset($this->repositories[$key]['users'][$name])) {
                unset($this->repositories[$key]['users'][$name]);
            }
        }

        $this->gitRepository->deleteFile($file);
        $confFile = $this->writeRepositoriesConf(null);
        $this->gitRepository->commitFile(array($file, $confFile), 'remove ssh key named ' . $name);
        $this->gitRepository->push();
    }

    public function getHistory() {
        return $this->gitRepository->getBranch('master')->getHistory();
    }

    public function createRepository($repositoryName, $owner) {
        if (!is_array($owner)) {
            $owner = array($owner);
        }

        $repository = array(
            'name'  => $repositoryName,
            'users' => array(),
        );

        foreach ($owner as $keyName) {
            $repository['users'][$keyName] = 'RW+';
        }

        $this->repositories[$repositoryName] = $repository;

        $this->writeRepositoriesConf('Create repository ' . $repositoryName);
    }

    public function removeRepository($repositoryName) {

        unset($this->repositories[$repositoryName]);

        $this->writeRepositoriesConf('Delete repository ' . $repositoryName);
    }

    public function addUserToRepository($repositoryName, $userKey, $write, $read) {
        if (!is_array($userKey)) {
            $userKey = array($userKey);
        }

        $rights = '';
        if ($read) {
            $rights .= 'R';
        }
        if ($write) {
            $rights .= 'W';
        }

        foreach ($userKey as $keyName) {
            $this->repositories[$repositoryName]['users'][$keyName] = $rights;
        }

        $this->writeRepositoriesConf(sprintf(
            'Add user(s) (%s) to repository %s',
            implode(', ', $userKey),
            $repositoryName));
    }

    public function removeUserToRepository($repositoryName, $userKey) {
        if (!is_array($userKey)) {
            $userKey = array($userKey);
        }

        foreach ($userKey as $keyName) {
            unset($this->repositories[$repositoryName]['users'][$keyName]);
        }

        $this->writeRepositoriesConf(sprintf(
            'Remove user(s) (%s) from repository %s',
            implode(', ', $userKey),
            $repositoryName));
    }

    public function getSshKeys() {
        return $this->sshKeys;
    }

}