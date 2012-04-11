<?php
namespace Glit\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;

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
            ->addOption('unix_user', '', InputOption::VALUE_REQUIRED, 'Unix user executing site and manipuling git.')

            ->addOption('admin_username', '', InputOption::VALUE_REQUIRED, 'Administrator username')
            ->addOption('admin_password', '', InputOption::VALUE_REQUIRED, 'Administrator password')
            ->addOption('admin_firstname', '', InputOption::VALUE_REQUIRED, 'Administrator firstname')
            ->addOption('admin_lastname', '', InputOption::VALUE_REQUIRED, 'Administrator lastname')
            ->addOption('admin_email', '', InputOption::VALUE_REQUIRED, 'Administrator email');
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

        $this->createDefaultUser($input, $output);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output) {
        // Display Header
        $dialog = $this->getDialogHelper();
        $output->writeln(array(
                              '<info></info>',
                              '<info>      Welcome in the Glit installer system</info>',
                              '<info></info>',
                              '<info>This wizard will guide you through all the installations step of the Glit projects platform.</info>',
                              '<info></info>',
                         ));

        // Get all parameters
        $unix_user = $dialog->ask(
            $output,
            $dialog->getQuestion('Unix user', $input->getOption('unix_user')),
            $input->getOption('unix_user')
        );

        $admin_username = $dialog->ask(
            $output,
            $dialog->getQuestion('Administrator username', $input->getOption('admin_username')),
            $input->getOption('admin_username')
        );

        $admin_password = $dialog->ask(
            $output,
            $dialog->getQuestion('Administrator password', $input->getOption('admin_password')),
            $input->getOption('admin_password')
        );

        $admin_firstname = $dialog->ask(
            $output,
            $dialog->getQuestion('Administrator firstname', $input->getOption('admin_firstname')),
            $input->getOption('admin_firstname')
        );

        $admin_lastname = $dialog->ask(
            $output,
            $dialog->getQuestion('Administrator lastname', $input->getOption('admin_lastname')),
            $input->getOption('admin_lastname')
        );

        $admin_email = $dialog->ask(
            $output,
            $dialog->getQuestion('Administrator email', $input->getOption('admin_email')),
            $input->getOption('admin_email')
        );

        // On sauvegarde les paramètres
        $input->setOption('unix_user', $unix_user);
        $input->setOption('admin_username', $admin_username);
        $input->setOption('admin_password', $admin_password);
        $input->setOption('admin_firstname', $admin_firstname);
        $input->setOption('admin_lastname', $admin_lastname);
        $input->setOption('admin_email', $admin_email);
    }

    private function createDefaultUser(InputInterface $input, OutputInterface $output) {

        // Create database
        $arguments = array(
            'username'      => $input->getOption('admin_username'),
            'password'      => $input->getOption('admin_password'),
            'firstname'     => $input->getOption('admin_firstname'),
            'lastname'      => $input->getOption('admin_lastname'),
            'email'         => $input->getOption('admin_email')
        );

        $commandUsersInit = $this->getApplication()->find('glit:users:initialize');
        $commandUsersInit->run(new ArrayInput($arguments), $output);
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
        $this->execProcess(sprintf('cp /home/%s/.ssh/id_rsa.pub /home/git/%s.pub && chmod 777 /home/git/glit.pub', $user, $user, $user));

        // TODO : check if needed (retrieved from gitlabhq install script
        $this->execProcess('sudo -u git -H sed -i \'s/0077/0007/g\' /home/git/share/gitolite/conf/example.gitolite.rc');

        // Setup pubkey of glit
        $this->execProcess(sprintf('sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; gl-setup -q /home/git/glit.pub"', $user));

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