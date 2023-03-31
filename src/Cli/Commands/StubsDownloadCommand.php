<?php

namespace Fastly\PhpRuntime\Cli\Commands;

use Fastly\PhpRuntime\Dependency\Stubs;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'stubs:download')]
class StubsDownloadCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Downloads Compute@Edge PHP Runtime stubs');

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_OPTIONAL,
            'The output file',
            'fastly-php-runtime.stubs.php'
        );

        $this->addOption(
            'runtime',
            'rt',
            InputOption::VALUE_OPTIONAL,
            'Runtime version',
            'latest'
        );

        $this->addOption(
            'no-gitignore',
            null,
            InputOption::VALUE_OPTIONAL,
            'Disable .gitignore editing',
            false
        );

        $this->addOption(
            'accept-gitignore-edit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Automatically accept .gitignore editing'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runtimeVersion = $input->getOption('runtime');
        $outputFile = $input->getOption('output');

        $noGitignore = (bool)$input->getOption('no-gitignore');
        $acceptGitignoreEdit = (bool)$input->getOption('accept-gitignore-edit');

        $output->writeln('Downloading stubs...');

        Stubs::downloadStubs($runtimeVersion, $outputFile);

        $output->writeln('Stubs downloaded');

        // todo: better way of detecting if current directory is a git repo
        if (!$noGitignore && is_dir('.git')) {
            $gitignoreEdited = $this->editGitignore($input, $output, $acceptGitignoreEdit, $outputFile);
            if ($gitignoreEdited) {
                $output->writeln('.gitignore edited');
            }
        }

        return 0;
    }

    private function editGitignore(
        InputInterface $input,
        OutputInterface $output,
        bool $acceptGitignoreEditing,
        string $stubsFile = 'fastly-php-runtime.stubs.php'
    ): bool {
        if (!$acceptGitignoreEditing) {
            // prompt for gitignore editing
            $confirmation = new ConfirmationQuestion(
                "Would you like to automatically add $stubsFile to .gitignore?",
                true
            );

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            if (!$helper->ask($input, $output, $confirmation)) {
                return false;
            }
        }

        file_put_contents('.gitignore', "\n/$stubsFile", FILE_APPEND);

        return true;
    }
}