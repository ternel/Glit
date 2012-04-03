<?php
namespace Glit\GitoliteBundle\Git;

use \Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Repository {

    private $path;
    private $logger;

    public static function cloneRepository(LoggerInterface $logger, $remote, $localPath) {
        self::staticExecProcessAsGlit($logger, $localPath, sprintf('git clone %s %s', $remote, $localPath));
        return new self($logger, $localPath);
    }

    public function __construct(LoggerInterface $logger, $path) {
        $this->logger = $logger;
        $this->path = $path;
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
     * @param $file
     * @return string
     */
    public function readFile($file) {
        return $this->execProcessAsGlit('cat ' . $file)->getOutput();
    }

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
        return self::staticExecProcessAsGlit($this->logger, $this->path, $command);
    }

    /**
     * @param $command
     * @return \Symfony\Component\Process\Process
     * @throws RuntimeException
     */
    protected static function staticExecProcessAsGlit(LoggerInterface $logger, $path, $command) {
        $command = sprintf('sudo -u glit -H sh -c \'cd %s; %s\'', $path, $command);

        $logger->addDebug('Execute : ' . $command);
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        $logger->addDebug('Result : ' . $process->getOutput());

        return $process;
    }
}