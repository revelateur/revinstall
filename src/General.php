<?php

namespace Revelateur\Revinstall\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class General extends Command
{
    public function decoration(OutputInterface $output)
    {
        $output->write(PHP_EOL.'<fg=red>
        ____  _______    _______   ________________    __    __ 
        / __ \/ ____/ |  / /  _/ | / / ___/_  __/   |  / /   / / 
       / /_/ / __/  | | / // //  |/ /\__ \ / / / /| | / /   / /  
      / _, _/ /___  | |/ // // /|  /___/ // / / ___ |/ /___/ /___
     /_/ |_/_____/  |___/___/_/ |_//____//_/ /_/  |_/_____/_____/</>'.PHP_EOL.PHP_EOL);
    }

    /**
     * Question required params.
     */
    protected function questionRequired(string $text, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        do {
            $question = new Question($text);
            $resp = $helper->ask($input, $output, $question);
        } while (empty($resp));

        return $resp;
    }

    /**
     * Question params.
     */
    protected function question(string $text, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question($text);

        return $helper->ask($input, $output, $question);
    }

    /**
     * Question params.
     */
    protected function confirm(string $text, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($text, false);

        return $helper->ask($input, $output, $question);
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
     * Get the wp-cli command for the environment.
     *
     * @return string
     */
    protected function findWP()
    {
        return __DIR__.'/../vendor/wp-cli/wp-cli/bin/wp';
    }

    /**
     * Verify that the .env exist.
     *
     * @return void
     */
    protected function verifyEnvExist()
    {
        if (!file_exists(getcwd().'/.env')) {
            throw new RuntimeException('File .env not found');
        }
    }

    /**
     * Run the given commands.
     *
     * @param array $commands
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output, array $env = [])
    {
        if (!$output->isDecorated()) {
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

        if (!empty($commands)) {
            foreach ($commands as $command) {
                $process = Process::fromShellCommandline($command, null, $env, null, null);
                $output->writeln('<options=bold,underscore>'.$command.'</>');
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }
        } else {
            die();
        }

        return $process;
    }

    public function runCommand($command, InputInterface $input, OutputInterface $output, array $env = [])
    {
        $process = Process::fromShellCommandline($command, null, $env, null, null);

        $output->writeln('<options=bold,underscore>'.$command.'</>');
        $process->run();

        return $process->getOutput();
    }
}
