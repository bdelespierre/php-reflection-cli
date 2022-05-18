<?php

namespace Bdelespierre\PhpReflectionCli\Composer;

use Composer\Autoload\ClassLoader;

final class ClassFinder
{
    public function __construct(
        private ClassLoader $loader
    ) {
    }

    /**
     * @return \Generator<class-string>
     */
    public function find(\SplFileInfo $dir): \Generator
    {
        if (! $dir->isDir()) {
            throw new \InvalidArgumentException("Invalid directory {$dir}");
        }

        $it = new \RecursiveDirectoryIterator($dir);
        $it = new \RecursiveIteratorIterator($it);
        $it = new \RegexIterator($it, '/\.php$/', \RegexIterator::MATCH);

        /** @var \SplFileInfo $file */
        foreach ($it as $file) {
            try {
                $class = $this->getClassFromPath($file);

                if ($this->inspect($file, $class)) {
                    yield $class;
                }
            } catch (\RuntimeException $e) {
                continue;
            }
        }
    }

    /**
     * @return class-string
     * @throws \RuntimeException when no class could be found
     */
    private function getClassFromPath(\SplFileInfo $file): string
    {
        if (false === $path = $file->getRealPath()) {
            throw new \RuntimeException("Error reading {$file}");
        }

        if (! is_null($class = $this->psr4PathLookup($path))) {
            return $class;
        }

        if (! is_null($class = $this->psr0PathLookup($path))) {
            return $class;
        }

        if (! is_null($class = $this->classmapLookup($path))) {
            return $class;
        }

        throw new \RuntimeException("Unable to find a suitable class for $path");
    }

    /**
     * @return class-string|null
     */
    private function psr4PathLookup(string $path): ?string
    {
        foreach ($this->loader->getPrefixesPsr4() as $namespace => $directories) {
            foreach ($directories as $directory) {
                if (false === $directory = realpath($directory)) {
                    continue; // that directory probably doesn't exist anymore...
                }

                if (str_starts_with($path, $directory)) {
                    /** @var class-string $class */
                    $class = $namespace . strtr(substr($path, strlen($directory) + 1, -4), DIRECTORY_SEPARATOR, '\\');
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    private function psr0PathLookup(string $path): ?string
    {
        /** @var array<string> $directories */
        foreach ($this->loader->getPrefixes() as $directories) {
            foreach ($directories as $directory) {
                if (false === $directory = realpath($directory)) {
                    continue; // that directory probably doesn't exist anymore...
                }

                if (str_starts_with($path, $directory)) {
                    /** @var class-string */
                    return strtr(substr($path, strlen($directory) + 1, -4), DIRECTORY_SEPARATOR, '\\');
                }
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    private function classmapLookup(string $path): ?string
    {
        /** @var class-string $class */
        foreach ($this->loader->getClassmap() as $class => $file) {
            if ($path == realpath($file)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param class-string $fqcn
     */
    private function inspect(\SplFileInfo $path, string $fqcn): bool
    {
        /** @var int<0,max>|false $pos */
        $pos = strrpos($fqcn, '\\');

        $classname = $fqcn;
        $namespace = null;

        if ($pos === 0) {
            $classname = substr($fqcn, 1);
            $namespace = null;
        }

        if ($pos > 0) {
            $classname = substr($fqcn, $pos + 1);
            $namespace = substr($fqcn, 0, $pos);
        }

        $classnameRegex = sprintf("/^((abstract|final)\s+)?(class|interface|trait)\s+%s/", preg_quote($classname, '/'));
        $namespaceRegex = sprintf("/^namespace\s+%s;/", preg_quote($namespace ?: '', '/'));

        $namespaceFound = is_null($namespace); // do not search an empty namespace
        $classnameFound = false;

        /** @var \SplFileObject<string> $file */
        $file = $path->openFile();
        $file->setFlags(
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::READ_AHEAD |
            \SplFileObject::DROP_NEW_LINE
        );

        foreach ($file as $line) {
            if (! $namespaceFound) {
                $namespaceFound = (bool) preg_match($namespaceRegex, $line);
            }

            if ($namespaceFound && ! $classnameFound) {
                $classnameFound = (bool) preg_match($classnameRegex, $line);
            }

            if ($namespaceFound && $classnameFound) {
                return true;
            }
        }

        return false;
    }
}
