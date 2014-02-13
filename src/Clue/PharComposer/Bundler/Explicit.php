<?php
namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Bundle;
use Clue\PharComposer\Logger;
use Clue\PharComposer\Package;
use Clue\PharComposer\Package\Autoload;
use Symfony\Component\Finder\Finder;

class Explicit implements BundlerInterface
{
    /**
     * package the bundler is for
     *
     * @type  Package
     */
    private $package;
    /**
     *
     * @type  Logger
     */
    private $logger;

    public function __construct(Package $package, Logger $logger)
    {
        $this->package = $package;
        $this->logger  = $logger;
    }

    private function logFile($file)
    {
        $this->logger->log('    adding "' . $file . '"');
    }

    /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle()
    {
        $bundle = new Bundle();
        $this->bundleBins($bundle);

        $autoload = $this->package->getAutoload();
        $this->bundlePsr0($bundle, $autoload);
        $this->bundleClassmap($bundle, $autoload);
        $this->bundleFiles($bundle, $autoload);

        return $bundle;
    }

    private function bundleBins(Bundle $bundle)
    {
        foreach ($this->package->getBins() as $bin) {
            $this->logFile($bin);
            $bundle->addFile($bin);
        }
    }

    private function bundlePsr0(Bundle $bundle, Autoload $autoload)
    {
        foreach ($autoload->getPsr0() as $path) {
            $dir = $this->package->getAbsolutePath($path);
            $bundle->addDir($this->createDirectory($dir));
        }
    }

    private function bundleClassmap(Bundle $bundle, Autoload $autoload)
    {
        foreach($autoload->getClassmap() as $path) {
            $this->addPath($bundle, $this->package->getAbsolutePath($path));
        }
    }

    private function bundleFiles(Bundle $bundle, Autoload $autoload)
    {
        foreach($autoload->getFiles() as $path) {
            $this->logFile($path);
            $bundle->addFile($this->package->getAbsolutePath($path));
        }
    }


    private function addPath(Bundle $bundle, $path)
    {
        if (is_dir($path)) {
            $bundle->addDir($this->createDirectory($path));
        } else {
            $bundle->addFile($path);
        }
    }

    private function createDirectory($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        $this->logger->log('    adding "' . $dir . '"');
        return Finder::create()
            ->files()
            //->filter($this->getBlacklistFilter())
            ->ignoreVCS(true)
            ->in($dir);
    }
}
