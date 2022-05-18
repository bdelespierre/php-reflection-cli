<?php

namespace Bdelespierre\PhpReflectionCli\Composer;

class AutoloaderFinder
{
    public function find(\SplFileInfo $dir): string
    {
        if (! $dir->isDir()) {
            throw new \InvalidArgumentException("Invalid directory {$dir}");
        }

        $parent = $dir->getPathname();

        do {
            $dir = $parent;

            if (file_exists("{$dir}/autoload.php")) {
                return "{$dir}/autoload.php";
            }

            if (file_exists("{$dir}/vendor/autoload.php")) {
                return "{$dir}/vendor/autoload.php";
            }

            $parent = dirname($dir);
        } while ($parent != $dir);

        throw new \RuntimeException("Unable to find the autoloader in {$dir}");
    }
}
