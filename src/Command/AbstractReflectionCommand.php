<?php

namespace Bdelespierre\PhpReflectionCli\Command;

use Symfony\Component\Console\Command\Command;
use Bdelespierre\PhpReflectionCli\Composer\AutoloaderFinder;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractReflectionCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            name: 'autoload',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Path to composer\'s autoloader.',
        );
    }

    protected function getLoader(\SplFileInfo $dir, InputInterface $input, OutputInterface $output): ClassLoader
    {
        if ($input->hasOption('autoload')) {
            /** @var string $autoload */
            $autoload = $input->getOption('autoload');
        }

        if (! isset($autoload)) {
            $autoload = (new AutoloaderFinder())->find($dir);
        }

        // write on stderr so this message isn't sent through pipes
        (new SymfonyStyle($input, $output))
            ->getErrorStyle()
            ->writeln("<fg=gray>Using autoloader {$autoload} for {$dir}</>");

        return require $autoload;
    }
}
