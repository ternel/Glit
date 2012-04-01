<?php
namespace Glit\GitoliteBundle\Admin;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class Gitolite {

    private $logger;
    private $adminUri = 'git@localhost:gitolite-admin.git';
    private $localDir = '/tmp/glit-gitolite-admin/';

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->initialize();
    }

    protected function initialize() {
        if (!file_exists($this->localDir)) {
            $this->execProcess(sprintf('sudo -u glit -H sh -c "mkdir %s && chmod -R 700 %s"', $this->localDir, $this->localDir));
            $this->execProcess(sprintf('sudo -H -u glit git clone %s %s', $this->adminUri, $this->localDir));
        }
    }

    /**
     * Add or update user key
     * @param $name
     * @param $sshKey
     */
    public function saveUserKey($name, $sshKey) {
        $file = $this->localDir . 'keydir' . DS . $name . '.pub';
        $this->execProcess(sprintf('sudo -u glit -H sh -c \'echo "%s" > %s\'', $sshKey, $file));
        $this->commitFile($file, 'define ssh key named ' . $name);
    }

    public function deleteUserKey($name) {
        $file = $this->localDir . 'keydir' . DS . $name . '.pub';
        $this->execProcess(sprintf('sudo -H -u glit rm %s', $file));
        $this->commitFile($file, 'remove ssh key named ' . $name);
    }

    private function commitFile($file, $reason) {
        $this->execProcess(sprintf('sudo -u glit -H sh -c \'cd %s; git add %s\'', $this->localDir, $file));
        $this->execProcess(sprintf('sudo -u glit -H sh -c \'cd %s; git commit -m "%s"\'', $this->localDir, $reason));
        $this->execProcess(sprintf('sudo -u glit -H sh -c \'cd %s; git push\'', $this->localDir));
    }

    /**
     * @param $command
     * @return \Symfony\Component\Process\Process
     * @throws RuntimeException
     */
    protected function execProcess($command) {
        $this->logger->addDebug('Execute : ' . $command);
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        $this->logger->addDebug('Execute : ' . $process->getOutput());

        return $process;
    }
}