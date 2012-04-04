<?php
namespace Glit\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class InstallCommand extends BaseInstallCommand {

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('glit:install')
            ->addArgument('unix_user', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Wich account the site is running ?');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // Save the output interface
        $this->output = $output;

        if (get_current_user() !== "root") {
            $output->writeln('<error>This script need to be run as root. Abort.</error>');
            return 1;
        }

        if (!$this->checkPackageInstalled('sudo')) {
            $output->writeln('<error>This script need the sudo program. Please install it and relaunch.</error>');
            return 101;
        }

        if (!$this->checkPackageInstalled('git')) {
            $output->writeln('<error>This script need the git program. Please install it and relaunch.</error>');
            return 102;
        }

        $unixUser = $input->getOption('unix_user');

        $this->createGitUser();

        $this->createGlitUser($unixUser);

        $this->installGitolite($unixUser);

        $this->installDatabase($output);

        $this->createDefaultUser($output);

        return 0;
    }

    private function createDefaultUser(OutputInterface $output) {
        $input = new ArrayInput(array());

        // Create database
        $commandCreateDb = $this->getApplication()->find('glit:users:initialize');
        $commandCreateDb->run($input, $output);
    }

    private function installDatabase(OutputInterface $output) {
        $input = new ArrayInput(array());

        // Create database
        $commandCreateDb = $this->getApplication()->find('doctrine:database:create');
        $commandCreateDb->run($input, $output);

        // Init schema
        $commandCreateSchema = $this->getApplication()->find('doctrine:schema:create');
        $commandCreateSchema->run($input, $output);
    }

    private function createGitUser() {
        $this->execProcess("adduser --system --shell /bin/sh --gecos 'git version control' --group --disabled-password --home /home/git git");
    }

    private function createGlitUser($user) {
        // Add user to git group
        $this->execProcess(sprintf("usermod -a -G git %s", $user));

        // Create sshKey
        $this->execProcess(sprintf("sudo -H -u %s ssh-keygen -q -N '' -t rsa -f /home/%s/.ssh/id_rsa", $user, $user));

        // add localhost public key to known_hosts
        $this->execProcess(sprintf('sudo -H -u %s echo "localhost" `cat /etc/ssh/ssh_host_rsa_key.pub` >> /home/%s/.ssh/known_hosts', $user, $user));
    }

    private function installGitolite($user) {
        // Clone source
        $this->execProcess('cd /home/git && exec sudo -H -u git git clone git://github.com/sitaramc/gitolite /home/git/gitolite');

        // Execute gitolite install script
        $this->execProcess('sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; /home/git/gitolite/src/gl-system-install"');

        // Copy glit publikKey
        $this->execProcess(sprintf('cp /home/%s/.ssh/id_rsa.pub /home/git/%s.pub && chmod 777 /home/git/%s.pub', $user, $user, $user));

        // TODO : check if needed (retrieved from gitlabhq install script
        $this->execProcess('sudo -u git -H sed -i \'s/0077/0007/g\' /home/git/share/gitolite/conf/example.gitolite.rc');

        // Setup pubkey of glit
        $this->execProcess(sprintf('sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; gl-setup -q /home/git/%s.pub"', $user));

        // Set repositories dir permissions
        $this->execProcess('chmod -R g+rwX /home/git/repositories/');
        $this->execProcess('chown -R git:git /home/git/repositories/');

        // clone admin repo to be sure your user has access to gitolite
        $this->execProcess(sprintf('sudo -u %s -H git clone git@localhost:gitolite-admin.git /tmp/gitolite-admin', $user));

        // check if file is cloned
        if (strpos($this->execProcess(sprintf('sudo -u %s -H ls /tmp/gitolite-admin/keydir/%s.pub', $user, $user))->getOutput(), sprintf('/tmp/gitolite-admin/keydir/%s.pub', $user)) === false) {
            throw new \Exception('Installation failed. Unable to dump gitolite-admin');
        }

        // if succeed  you can remove it
        $this->execProcess('sudo rm -rf /tmp/gitolite-admin');
    }

    protected function log($text) {
        $this->output->writeln($text);
    }
}