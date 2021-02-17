<?php

namespace League\Flysystem\Replicate;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class ReplicateAdapter extends AbstractAdapter
{
    /**
     * @var AdapterInterface
     */
    protected $replica;

    /**
     * @var AdapterInterface
     */
    protected $source;

    /**
     * Constructor.
     *
     * @param AdapterInterface $source
     * @param AdapterInterface $replica
     */
    public function __construct(AdapterInterface $source, AdapterInterface $replica)
    {
        $this->source = $source;
        $this->replica = $replica;
    }

    /**
     * Returns the replica adapter.
     *
     * @return AdapterInterface
     */
    public function getReplicaAdapter()
    {
        return $this->replica;
    }

    /**
     * Returns the source adapter.
     *
     * @return AdapterInterface
     */
    public function getSourceAdapter()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        if (! $this->source->write($path, $contents, $config)) {
            return false;
        }

        return $this->replica->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        if (! $this->source->writeStream($path, $resource, $config)) {
            return false;
        }

        if (! $resource = $this->ensureSeekable($resource, $path)) {
            return false;
        }

        return $this->replica->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function move($path, $newpath)
    {
        if (! $this->source->move($path, $newpath)) {
            return false;
        }

        return $this->replica->move($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        if (! $this->source->copy($path, $newpath)) {
            return false;
        }

        return $this->replica->copy($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        if (! $this->source->delete($path)) {
            return false;
        }

        if ($this->replica->fileExists($path)) {
            return $this->replica->delete($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        if (! $this->source->deleteDir($dirname)) {
            return false;
        }

        return $this->replica->deleteDir($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory($dirname, Config $config)
    {
        if (! $this->source->createDirectory($dirname, $config)) {
            return false;
        }

        return $this->replica->createDirectory($dirname, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists($path)
    {
        return $this->source->fileExists($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->source->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->source->readStream($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->source->listContents($directory, $recursive);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->source->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function fileSize($path)
    {
        return $this->source->fileSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType($path)
    {
        return $this->source->mimeType($path);
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified($path)
    {
        return $this->source->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function visibility($path)
    {
        return $this->source->visibility($path);
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        if (! $this->source->setVisibility($path, $visibility)) {
            return false;
        }

        return $this->replica->setVisibility($path, $visibility);
    }

    /**
     * Rewinds the stream, or returns the source stream if not seekable.
     *
     * @param resource $resource The resource to rewind.
     * @param string   $path     The path where the resource exists.
     *
     * @return resource A stream set to position zero.
     */
    protected function ensureSeekable($resource, $path)
    {
        if (Util::isSeekableStream($resource) && rewind($resource)) {
            return $resource;
        }

        $stream = $this->source->readStream($path);

        return $stream ? $stream['stream'] : false;
    }
}
