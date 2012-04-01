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
            ->setName('glit:install');
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

        //$this->createGitUser();

        //$this->createGlitUser();

        $this->installGitolite();

        return 0;
    }

    private function createGitUser() {
        $this->execProcess("adduser --system --shell /bin/sh --gecos 'git version control' --group --disabled-password --home /home/git git");
    }

    private function createGlitUser() {
        // Create User
        $this->execProcess("adduser --disabled-login --gecos 'glit system' --home /home/glit glit");

        // Add user to git group
        $this->execProcess("usermod -a -G git glit");

        // Create sshKey
        $this->execProcess("sudo -H -u glit ssh-keygen -q -N '' -t rsa -f /home/glit/.ssh/id_rsa");

        // add localhost public key to known_hosts
        $this->execProcess('sudo -H -u glit echo "localhost" `cat /etc/ssh/ssh_host_rsa_key.pub` >> /home/glit/.ssh/known_hosts');
    }

    private function installGitolite() {
        // Clone source
        $this->execProcess('cd /home/git && exec sudo -H -u git git clone git://github.com/sitaramc/gitolite /home/git/gitolite');

        // Execute gitolite install script
        $this->execProcess('sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; /home/git/gitolite/src/gl-system-install"');

        // Copy glit publikKey
        $this->execProcess('cp /home/glit/.ssh/id_rsa.pub /home/git/glit.pub && chmod 777 /home/git/glit.pub');

        // TODO : check if needed (retrieved from gitlabhq install script
        $this->execProcess('sudo -u git -H sed -i \'s/0077/0007/g\' /home/git/share/gitolite/conf/example.gitolite.rc');

        // Setup pubkey of glit
        $this->execProcess('sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; gl-setup -q /home/git/glit.pub"');

        // Set repositories dir permissions
        $this->execProcess('chmod -R g+rwX /home/git/repositories/');
        $this->execProcess('chown -R git:git /home/git/repositories/');

        // clone admin repo to be sure your user has access to gitolite
        $this->execProcess('sudo -u glit -H git clone git@localhost:gitolite-admin.git /tmp/gitolite-admin');

        // check if file is cloned
        if (strpos($this->execProcess('sudo -u glit -H ls /tmp/gitolite-admin/keydir/glit.pub')->getOutput(), '/tmp/gitolite-admin/keydir/glit.pub') === false) {
            throw new \Exception('Installation failed. Unable to dump gitolite-admin');
        }

        // if succeed  you can remove it
        $this->execProcess('sudo rm -rf /tmp/gitolite-admin');
    }

    protected function log($text) {
        $this->output->writeln($text);
    }
}