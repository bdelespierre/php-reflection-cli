<?php

namespace Bdelespierre\PhpReflectionCli\Command;

use Bdelespierre\PhpReflectionCli\Command\Output\ReflectionRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'find:methods',
    description: 'Finds the methods of the given class.',
    hidden: false,
    aliases: ['methods'],
)]
final class MethodFinderCommand extends AbstractReflectionCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addArgument(
            name: 'class',
            mode: InputArgument::REQUIRED,
            description: 'The PHP class.',
        );

        $this->addOption(
            name: 'hide-inherited',
            mode: InputOption::VALUE_NONE,
            description: 'Do not display inherited methods.',
        );

        $this->addOption(
            name: 'hide-parameter-types',
            mode: InputOption::VALUE_NONE,
            description: 'Do not display parameter types.',
        );

        $this->addOption(
            name: 'hide-namespaces',
            mode: InputOption::VALUE_NONE,
            description: 'Display class name instead of FQCN.',
        );

        $this->addOption(
            name: 'one-line',
            mode: InputOption::VALUE_NONE,
            description: 'Print method prototypes on a single line.',
        );

        $this->addOption(
            name: 'short',
            mode: InputOption::VALUE_NONE,
            description: 'Equivalent of --hide-inherited --hide-parameter-types --hide-namespaces --one-line.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $renderer = new ReflectionRenderer();

        if ($input->getOption('hide-namespaces') || $input->getOption('short')) {
            $renderer->setOption(ReflectionRenderer::OPT_SHOW_NAMESPACES, false);
        }

        if ($input->getOption('hide-parameter-types') || $input->getOption('short')) {
            $renderer->setOption(ReflectionRenderer::OPT_SHOW_PARAMETER_TYPES, false);
        }

        if ($input->getOption('one-line') || $input->getOption('short')) {
            $renderer->setOption(ReflectionRenderer::OPT_MULTILINE_METHOD_ARGUMENTS, false);
        }

        /** @var class-string $class */
        $class = $input->getArgument('class');

        if (! class_exists($class)) {
            $this
                ->getLoader(new \SplFileInfo('.'), $input, $output)
                ->loadClass($class);
        }

        $reflect = new \ReflectionClass($class);
        $methods = $reflect->getMethods();

        // write on stderr so this message isn't sent through pipes
        (new SymfonyStyle($input, $output))
            ->getErrorStyle()
            ->writeln("<fg=gray>Methods of {$reflect->getName()}</>");

        if ($input->getOption('hide-inherited') || $input->getOption('short')) {
            $methods = array_filter(
                $methods,
                fn (\ReflectionMethod $m) => $m->getDeclaringClass()->getName() == $reflect->getName()
            );
        }

        usort($methods, [$this, 'sortMethods']);

        foreach ($methods as $method) {
            $output->writeln(
                $renderer->renderMethodPrototype($method),
            );
        }

        return self::SUCCESS;
    }

    private function sortMethods(\ReflectionMethod $a, \ReflectionMethod $b): int
    {
        return $this->getMethodOrder($a) <=> $this->getMethodOrder($b);
    }

    private function getMethodOrder(\ReflectionMethod $method): int
    {
        return match ($method->getName()) {
            '__construct' => 0,
            '__destruct' => 1,
            default => match (true) {
                str_starts_with('__', $method->getName()) => 2,
                default => match (true) {
                    $method->isAbstract() => 3,
                    $method->isPublic() => 4,
                    $method->isProtected() => 5,
                    default => 6,
                },
            },
        };
    }
}
