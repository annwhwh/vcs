<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Vcs;

use Webcreate\Vcs\Common\Pointer;
use Webcreate\Vcs\Common\FileInfo;
use Webcreate\Vcs\Svn\WorkingCopy;
use Webcreate\Vcs\Common\Commit;
use Webcreate\Vcs\Svn\AbstractSvn;

/**
 * Subversion client
 *
 * Note: This class should only contain methods that are also in the
 * VcsInterface. Other methods should go into the AbstractSvn class.
 *
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 */
class Svn extends AbstractSvn implements VcsInterface
{
    protected $wc;

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::checkout()
     */
    public function checkout($dest)
    {
        $this->wc = new WorkingCopy($this, $dest);

        return $this->wc->checkout();
    }

    /**
     * Add to version control
     *
     * @param string $path
     * @throws \RuntimeException
     * @return string
     */
    public function add($path)
    {
        if (null === $this->wc) {
            throw new \RuntimeException('Working copy not initialized, have you tried checkout()?');
        }

        return $this->wc->add($path);
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::commit()
     */
    public function commit($message)
    {
        if (null === $this->wc) {
            throw new \RuntimeException('Working copy not initialized, have you tried checkout()?');
        }

        return $this->wc->commit($message);
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::status()
     */
    public function status($path = null)
    {
        if (null === $this->wc) {
            throw new \RuntimeException('Working copy not initialized, have you tried checkout()?');
        }

        return $this->wc->status($path);
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::import()
     */
    public function import($src, $path, $message)
    {
        return $this->execute('import', array($src, $this->getSvnUrl($path), '-m' => $message));
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::export()
     */
    public function export($path, $dest)
    {
        if (is_dir($dest)) {
            $dest = $dest . '/' . basename($path);
        }

        return $this->execute('export', array($this->getSvnUrl($path), $dest));
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::ls()
     */
    public function ls($path)
    {
        return $this->execute('list', array('--xml' => true, $this->getSvnUrl($path)));
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::log()
     */
    public function log($path, $revision=null, $limit=null)
    {
        return $this->execute('log', array(
                '-r' => $revision ? $revision : false,
                '--limit' => $limit ? $limit : false,
                '--xml' => true,
                $this->getSvnUrl($path)
                )
        );
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::cat()
     */
    public function cat($path)
    {
        return $this->execute('cat', array($this->getSvnUrl($path)));
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::diff()
     */
    public function diff($oldPath, $newPath, $oldRevision = 'HEAD', $newRevision = 'HEAD', $summary = true)
    {
        $xml = ($summary ? true : false);

        return $this->execute('diff', array(
                $this->getSvnUrl($oldPath) . '@' . $oldRevision,
                $this->getSvnUrl($newPath) . '@' . $newRevision,
                '--summarize' => $summary,
                '--xml' => $xml,
        ));
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::branches()
     */
    public function branches()
    {
        $result = $this->execute('list', array('--xml' => true, $this->getUrl() . '/' . $this->basePaths['branches']));

        $branches = array('trunk');
        foreach($result as $fileinfo) {
            $branches[] = $fileinfo->getName();
        }

        return $branches;
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Vcs.VcsInterface::tags()
     */
    public function tags()
    {
        $result = $this->execute('list', array('--xml' => true, $this->getUrl() . '/' . $this->basePaths['tags']));

        $tags = array();
        foreach($result as $fileinfo) {
            $tags[] = $fileinfo->getName();
        }

        return $tags;
    }
}