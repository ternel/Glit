<?php
namespace Glit\GitoliteBundle\Admin;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Glit\GitoliteBundle\Git\Repository;

class Gitolite {

    private $logger;
    private $adminUri = 'git@localhost:gitolite-admin.git';
    private $localDir = '/tmp/glit-gitolite-admin/';

    /** @var Repository */
    private $gitRepository;

    private $sshKeys;
    private $repositories;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->initialize();
    }

    protected function initialize() {
        if (!file_exists($this->localDir)) {
            $this->gitRepository = Repository::cloneRepository($this->logger, $this->adminUri, $this->localDir);
        }
        else {
            $this->gitRepository = new Repository($this->logger, $this->localDir);
        }

        // Load keys and repository
        $this->loadUserKeyList();
        $this->loadRepositories();

        print_r($this->repositories);
        $this->writeRepositoriesConf();
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

        foreach ($lines as $line) {
            $temp = array_merge(array_filter(explode(" ", $line), 'strlen'));

            if (count($temp) <= 0)
                continue;

            if ($temp[0] == 'repo') {
                if (null !== $tempRepository) {
                    $this->repositories[$tempRepository['name']] = $tempRepository;
                }

                $tempRepository = array(
                    'name' => $temp[1],
                    'users' => array()
                );
            }
            else {
                $tempRepository['users'][$temp[2]] = $temp[0];
            }
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

        $confFile = 'keygen' . DS . 'gitolite.conf';
        $this->gitRepository->saveFile($confFile, $conf);
        $this->gitRepository->commitFile($confFile, $commitMessage);
        $this->gitRepository->push();
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
     * @param $name
     */
    public function deleteUserKey($name) {
        $file = 'keydir' . DS . $name . '.pub';

        unset ($this->sshKeys[$name]);
        $this->gitRepository->deleteFile($file);

        $this->gitRepository->commitFile($file, 'remove ssh key named ' . $name);
        $this->gitRepository->push();
    }

    public function createRepository($repositoryName, $owner) {
        if (!is_array($owner)) {
            $owner = array($owner);
        }

        $repository = array(
            'name' => $repositoryName,
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

}