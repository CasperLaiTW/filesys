<?php namespace Crip\Filesys\Services;

use Crip\Core\Contracts\ICripObject;
use Crip\Core\Helpers\FileSystem;
use Crip\Core\Helpers\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use League\Flysystem\StorageAttributes;

/**
 * Class BlobMetadata
 * @package Crip\Filesys\Services
 */
class BlobMetadata implements ICripObject
{
    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    private $storage;

    /**
     * @var bool
     */
    private $isExistExecuted = false;
    /**
     * @var bool
     */
    private $exists = false;
    /**
     * @var null
     */
    private $path = null;
    /**
     * @var
     */
    private $lastModified;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $dir;
    /**
     * @var string
     */
    private $mimeType = 'dir';
    /**
     * @var null
     */
    private $extension = null;
    /**
     * @var
     */
    private $size;
    /**
     * @var
     */
    private $type;

    /**
     * BlobMetadata initializer.
     * @param $path
     * @return BlobMetadata $this
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \League\Flysystem\FilesystemException
     */
    public function init($path)
    {
        $this->storage = app()->make('filesystem');
        $this->path = $path;

        $this->type = blank(File::extension($path)) ? 'dir' : 'file';
        [$this->dir, $this->name] = FileSystem::splitNameFromPath($path);

        if ($this->isFile()) {
            $this->size = $this->storage->fileSize($this->path);
            $this->lastModified = $this->storage->lastModified($this->path);
            [$this->name, $this->extension] = $this->splitNameAndExtension($this->name);
            $this->extension = strtolower($this->extension);
            $this->mimeType = self::guessMimeType($this->extension, $this->isFile());
        }

        return $this;
    }

    /**
     * @param StorageAttributes $glob
     * @return $this
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function initGlob(StorageAttributes $glob)
    {
        [$this->dir, $this->name] = FileSystem::splitNameFromPath($glob->path());
        [$this->name, $this->extension] = $this->splitNameAndExtension($this->name);

        $this->storage = app()->make('filesystem');
        $this->path = $glob->path();
        $this->type = $glob->type();
        $this->size = $glob->isFile() ? $glob->fileSize() : 0;
        $this->lastModified = $glob->lastModified();
        $this->mimeType = $glob->isDir() ? 'dir' : ($glob->mimeType() ?? self::guessMimeType($this->extension, $glob->isFile()));

        return $this;
    }

    /**
     * Get debug info of current metadata file.
     * @return array
     */
    public function debugInfo()
    {
        return [
            'exists' => $this->exists(),
            'isFile' => $this->isFile(),
            'isImage' => $this->isImage(),
            'path' => $this->path,
            'lastModified' => $this->lastModified,
            'name' => $this->name,
            'dir' => $this->dir,
            'mimeType' => $this->mimeType,
            'extension' => $this->extension,
            'size' => $this->size,
            'type' => $this->type,
            'getFullName' => $this->getFullName()
        ];
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return $this->type === 'file';
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        if ($this->exists()) {
            return substr($this->mimeType, 0, 5) === 'image';
        }

        return false;
    }

    /**
     * @param bool $removeUserPath
     * @return string
     */
    public function getPath(bool $removeUserPath = false): string
    {
        if ($removeUserPath) {
            return $this->normalizePath($this->path);
        }

        return $this->path;
    }

    /**
     * @return int
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $removeUserPath
     * @return string
     */
    public function getDir(bool $removeUserPath = false): string
    {
        if ($removeUserPath) {
            return $this->normalizePath($this->dir);
        }

        return $this->dir;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return null|string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if ($this->isFile()) {
            return $this->name . '.' . $this->extension;
        }

        return $this->name;
    }

    /**
     * Split name and extension.
     * @param $fullName
     * @return array [name, extension]
     */
    private function splitNameAndExtension($fullName)
    {
        $info = pathinfo($fullName);

        return [Arr::get($info, 'filename'), Arr::get($info, 'extension')];
    }

    /**
     * Try guess mime type from path
     * @param string $extension
     * @param bool $isFile
     * @return string
     */
    public static function guessMimeType($extension = '', $isFile = true)
    {
        if ($isFile) {
            $map = config('cripfilesys.mime.map');
            return isset($map[$extension]) ?
                $map[$extension] : 'text/plain';
        }

        return 'dir';
    }

    /**
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $userFolder = Str::normalizePath(config('cripfilesys.user_folder'));

        if ($userFolder !== '') {
            $path = str_replace_first($userFolder, '', $path);
        }

        return Str::normalizePath($path);
    }
}
