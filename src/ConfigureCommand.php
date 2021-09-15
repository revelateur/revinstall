<?php

namespace Revelateur\Revinstall\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;

class ConfigureCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Install and configure the project');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(PHP_EOL.'<fg=red>
        ____  _______    _______   ________________    __    __ 
        / __ \/ ____/ |  / /  _/ | / / ___/_  __/   |  / /   / / 
       / /_/ / __/  | | / // //  |/ /\__ \ / / / /| | / /   / /  
      / _, _/ /___  | |/ // // /|  /___/ // / / ___ |/ /___/ /___
     /_/ |_/_____/  |___/___/_/ |_//____//_/ /_/  |_/_____/_____/</>'.PHP_EOL.PHP_EOL);

        $composer = $this->findComposer();

        $commands = [
            $composer." u",
            'npm install',
            'npm run prod'
        ];

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {
            $output->writeln(PHP_EOL.'<comment>Application ready! Build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  array  $env
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output, array $env = [])
    {
        if (! $output->isDecorated()) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --quiet';
            }, $commands);
        }

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command, null, $env, null, null);
            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                try {
                    // $process->setTty(true);
                } catch (RuntimeException $e) {
                    $output->writeln('Warning: '.$e->getMessage());
                }
            }
    
            $output->writeln('<options=bold,underscore>'.$command.'</>');
            $process->run();
        }

        return $process;
    }
}
