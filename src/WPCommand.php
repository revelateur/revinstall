<?php

namespace Revelateur\Revinstall\Console;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WPCommand extends General
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('wp')
            ->setDescription('Install and configure a Wordpress project');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->decoration($output);

        $composer = $this->findComposer();
        $wp = $this->findWP();

        $this->verifyEnvExist();
        $dotenv = Dotenv::createImmutable(getcwd());
        $dotenv->load();

        $this->createDatabase($input, $output);
        $url = $this->questionRequired('Entrer le domaine à utiliser : ', $input, $output);
        $title = $this->questionRequired('Entrer le titre du site : ', $input, $output);
        $user = $this->questionRequired('Entrer le username de l\'admin : ', $input, $output);
        $pass = $this->questionRequired('Entrer le mot de passe de l\'admin : ', $input, $output);
        $mail = $this->questionRequired('Entrer l\'adresse mail de l\'admin : ', $input, $output);
        $chown = $this->confirm('Chown le dossier uploads ? Laisser vide pour non : ', $input, $output);

        chdir(getcwd());
        $commands = [
            $composer.' u',
            $wp.' core install --url='.$url.' --title="'.$title.'" --admin_user='.$user.' --admin_password='.$pass.' --admin_email='.$mail,
            $wp.' theme activate '.$_ENV['WP_THEME'],
            $wp.' language core activate fr_FR',
            $wp.' plugin activate --all',
            $wp.' menu create "Menu Principal"',
            $wp.' menu location assign menu-principal primary',
            $wp.' menu create "Pied de page"',
            $wp.' menu location assign pied-de-page footer',
            $wp.' post delete 1 --force',
            $wp." option update permalink_structure '/%category%/%postname%/'",
        ];

        if (!empty($chown)) {
            $commands[] = 'sudo chown www-data:www-data -R ./public/uploads';
            $commands[] = 'sudo chmod 775 ./public/wp-config.php';
        }

        $this->runCommands($commands, $input, $output);
        $this->defineHomePage($input, $output, $wp);

        $commands = [
            'npm install',
            'npm run build',
        ];

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {
            $output->writeln(PHP_EOL.'<comment>Application ready! Build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

    /**
     * Create database by .env file.
     */
    protected function createDatabase(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            'mysql -h '.$_ENV['DB_HOST'].' -u '.$_ENV['DB_USER'].' -p'.$_ENV['DB_PASSWORD'].' -e "CREATE DATABASE IF NOT EXISTS '.$_ENV['DB_NAME'].'"',
        ];

        $this->runCommands($commands, $input, $output);
    }

    /*
     * Create and define the home page
     */
    public function defineHomePage(InputInterface $input, OutputInterface $output, $wp)
    {
        // Create home page
        $command = $wp.' post create --post_type=page --post_title="Accueil" --post_status=publish';
        $post = $this->runCommand($command, $input, $output);
        preg_match('/(?<=post )(.*)(?=.)/', $post, $homeID);

        // Definie home page in read option
        $this->runCommands([
            $wp.' option update show_on_front "page"',
            $wp.' option update page_on_front "'.$homeID[0].'"',
            $wp.' menu item add-post menu-principal '.$homeID[0],
            $wp.' menu item add-post menu-principal 2',
        ], $input, $output);
    }
}
