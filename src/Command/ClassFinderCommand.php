<?php

namespace Bdelespierre\PhpReflectionCli\Command;

use Bdelespierre\PhpReflectionCli\Command\Output\ReflectionRenderer;
use Bdelespierre\PhpReflectionCli\Composer\ClassFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'find:classes',
    description: 'Find PHP classes in the given directories.',
    hidden: false,
    aliases: ['classes'],
)]
final class ClassFinderCommand extends AbstractReflectionCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addArgument(
            name: 'dir',
            mode: InputArgument::IS_ARRAY,
            description: 'The directory containing the files.',
            default: ['.'],
        );

        $this->addOption(
            name: 'escape',
            shortcut: 'e',
            mode: InputOption::VALUE_NONE,
            description: 'Escape namespace separator using double-backslashes.',
        );

        $this->addOption(
            name: 'tree',
            mode: InputOption::VALUE_NONE,
            description: 'Display classes as a tree.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classes = new \AppendIterator();

        /** @var array<string> $directories */
        $directories = $input->getArgument('dir');

        foreach ($directories as $dir) {
            $dir = new \SplFileInfo($dir);

            if (! $dir->isDir()) {
                throw new \RuntimeException("Not a directory {$dir}");
            }

            $classes->append($this->findClassesIn($dir, $input, $output));
        }

        $input->getOption('tree')
            ? $this->showClassTree($this->prepareClassTree($classes), $output)
            : $this->showClassList($classes, $input, $output);

        return self::SUCCESS;
    }

    /**
     * @return \Generator<class-string>
     */
    private function findClassesIn(\SplFileInfo $dir, InputInterface $input, OutputInterface $output): \Generator
    {
        $loader = $this->getLoader($dir, $input, $output);
        $loader->register();

        $finder = new ClassFinder($loader);

        foreach ($finder->find($dir) as $class) {
            yield $class;
        }
    }

    /**
     * @param iterable<class-string> $classes
     */
    private function showClassList(iterable $classes, InputInterface $input, OutputInterface $output): void
    {
        $renderer = new ReflectionRenderer();

        if ($input->getOption('escape') || ! stream_isatty(STDOUT)) {
            $renderer->setOption(ReflectionRenderer::OPT_ESCAPE_NAMESPACES, true);
        }

        foreach ($classes as $class) {
            $output->writeln(
                $renderer->renderClassName($class)
            );
        }
    }

    /**
     * @param iterable<class-string> $classes
     * @return array<mixed>
     */
    private function prepareClassTree(iterable $classes): array
    {
        $tree = [];

        foreach ($classes as $class) {
            $parts = explode('\\', $class);
            $current = &$tree;

            foreach ($parts as $part) {
                if (empty($current[$part])) { /* @phpstan-ignore-line */
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        return $tree;
    }

    /**
     * @param array<mixed> $tree
     */
    private function showClassTree(array $tree, OutputInterface $output, string $prefix = "", int $depth = 0): void
    {
        $i = 0;

        /** @var array<mixed> $leaves */
        foreach ($tree as $branch => $leaves) {
            $i++;
            $isLast = $i == count($tree);
            $hasChildren = !empty($leaves);
            $decorated = $prefix . ($isLast ? '└─' : '├─') . ($hasChildren ? '┬' : '─');
            $newPrefix = $prefix . ($isLast ? '  ' : '│ ');

            if ($depth == 0) {
                $decorated = '';
                $newPrefix = '';
            }

            $output->writeln("<fg=gray>{$decorated}</>{$branch}");

            if ($hasChildren) {
                $this->showClassTree($leaves, $output, $newPrefix, $depth + 1);
            }
        }
    }
}
