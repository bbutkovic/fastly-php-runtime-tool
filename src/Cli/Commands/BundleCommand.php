<?php

namespace Fastly\PhpRuntime\Cli\Commands;

use Fastly\PhpRuntime\Dependency\Runtime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'bundle')]
class BundleCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Bundles the PHP code along with the runtime');

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_OPTIONAL,
            'The output file',
            'bundle.wasm'
        );

        $this->addOption(
            'runtime',
            'rt',
            InputOption::VALUE_OPTIONAL,
            'Path to runtime or runtime version',
            'latest'
        );

        $this->addOption(
            'type',
            't',
            InputOption::VALUE_OPTIONAL,
            'Code type',
              'script'
        );

        $this->addArgument('code', InputArgument::REQUIRED, 'Path to PHP code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runtime = $input->getOption('runtime');
        if (Runtime::exists($runtime)) {
            $runtimePath = $runtime;
        } else {
            if ($runtime === 'latest') {
                $runtime = Runtime::getLatestRuntimeVersion();
            }

            Runtime::ensureRuntimeVersion($runtime);

            $runtimePath = Runtime::getRuntimeVersionPath($runtime);
        }

        $code = $input->getArgument('code');
        if (!file_exists($code)) {
            throw new \RuntimeException('File not found: ' . $code);
        }

        $codeType = $input->getOption('type');
        $output->writeln("Starting to bundle $code ($codeType) along with the runtime");

        if ($output->isVerbose()) {
            $output->writeln('Using runtime: ' . $runtimePath);
        }

        $bundleOutput = $input->getOption('output');

        // todo: download wizer dynamically
        $wizer = new Process([
            'wizer',
            '--allow-wasi',
            '--wasm-bulk-memory=true',
            '-o',
            $bundleOutput,
            $runtimePath
        ]);

        $wizer->setInput(file_get_contents($code));

        if ($output->isVerbose()) {
            $running = "Running wizer";
            if ($output->isVeryVerbose()) {
                $wizerCli = $wizer->getCommandLine();
                $running .= " ($wizerCli)";
            }

            $running .= " with $code";

            $output->writeln($running);
        }

        $wizer->run();

        if ($output->isVeryVerbose()) {
            $output->write($wizer->getErrorOutput());
            $output->write($wizer->getOutput());
        }

        if (!$wizer->isSuccessful()) {
            $exitCode = $wizer->getExitCode();
            $output->writeln("Error creating bundle at $bundleOutput (exit code $exitCode)");

            return $exitCode;
        }

        $output->writeln("Bundle created at $bundleOutput");
        return 0;
    }
}