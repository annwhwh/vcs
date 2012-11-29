<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Vcs\Svn;

use Webcreate\Util\Cli;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Svnadmin
{
    protected $svndir;
    protected $cli;
    protected $bin;

    public function __construct($svndir, $bin = '/usr/local/bin/svnadmin', Cli $cli = null)
    {
        $this->svndir = $svndir;
        $this->bin = $bin;
        $this->cli = $cli ?: new Cli();
    }

    public function create($name)
    {
        $path = $this->getReposDir($name);

        return $this->exec('create', array($path));
    }

    public function destroy($name)
    {
        $path = $this->getReposDir($name);

        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('Repository path %s does not exists', $path));
        }

        $filesystem = new Filesystem();
        $filesystem->remove($path);
    }

    /**
     * Creates a backup of svnserve.conf and links
     * a global svnserve.conf in the repository
     *
     * @param string $name         name of the repository
     * @param string $svnserveFile full path to the global svnserve.conf
     * @throws ProcessFailedException
     * @throws \RuntimeException
     */
    public function svnconf($name, $svnserveFile)
    {
        $filesystem = new Filesystem();
        $filesystem->rename($this->getReposDir($name) . '/conf/svnserve.conf', $this->getReposDir($name) . '/conf/svnserve.conf~');
        $filesystem->symlink($svnserveFile, $this->getReposDir($name));
    }

    protected function getReposDir($name)
    {
        return $this->svndir . '/' . $name;
    }

    protected function exec($command, array $arguments = array())
    {
        $command = $this->bin . ' ' . $command;

        $commandline = $this->cli->prepare($command, $arguments);

        if ($this->cli->execute($commandline) <> 0) {
            throw new ProcessFailedException($this->cli->getProcess());
        } elseif ($message = $this->cli->getErrorOutput()) {
            throw new \RuntimeException($message);
        }

        return $this->cli->getOutput();
    }
}