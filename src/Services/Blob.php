<?php namespace Crip\Filesys\Services;

use Crip\Core\Contracts\ICripObject;
use Crip\Core\Helpers\Str;
use Crip\Core\Support\PackageBase;
use Crip\Filesys\App\File;
use Crip\Filesys\App\Folder;
use League\Flysystem\StorageAttributes;

/**
 * Class Blob
 * @package Crip\Filesys\Services
 */
class Blob implements ICripObject
{
    /**
     * @var
     */
    public $path;

    /**
     * @var BlobMetadata
     */
    public $metadata;

    /**
     * @var PackageBase
     */
    private $package;

    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    private $storage;

    /**
     * @var null
     */
    private $thumbsDetails = null;
    /**
     * @var StorageAttributes
     */
    private StorageAttributes $glob;

    /**
     * Blob constructor.
     * @param PackageBase $package
     */
    public function __construct(PackageBase $package)
    {
        $this->package = $package;
        $this->storage = app()->make('filesystem');
    }

    /**
     * @param StorageAttributes $glob
     * @return $this
     */
    public function setGlob(StorageAttributes $glob)
    {
        $this->glob = $glob;
        return $this;
    }

    /**
     * Set current blob path property.
     * @param string $path
     * @return Blob $this
     */
    public function setPath($path = '')
    {
        $userFolder = Str::normalizePath(config('cripfilesys.user_folder'));

        if ($userFolder !== '' && !starts_with($path, $userFolder)) {
            $path = empty($path) ? '' : $path;
            $path = $this->prefixPath($path, $userFolder);
        }

        $this->path = Str::normalizePath($path);

        return $this;
    }

    /**
     * @param BlobMetadata $metadata
     * @return File|Folder
     * @throws \Exception
     */
    public function fullDetails($metadata = null)
    {
        if ($metadata) {
            $this->metadata = $metadata;
        } else if ($this->glob) {
            if (!$this->path) {
                $this->setPath($this->glob->path());
            }

            $this->metadata = (new BlobMetadata())->initGlob($this->glob);
        } else {
            $this->metadata = (new BlobMetadata())->init($this->path);
        }

        if (!$this->metadata->exists()) {
            throw new \Exception('File not found');
        }

        $result = $this->metadata->isFile() ?
            new File($this) :
            new Folder($this);

        return $result;
    }

    /**
     * Get blob type.
     * @return string
     */
    public function getType()
    {
        return $this->metadata->isFile() ? 'file' : 'dir';
    }

    /**
     * Get blob media type.
     * @return string
     */
    public function getMediaType()
    {
        $mime = $this->getMime();

        if ($mime == 'file') {
            return $mime;
        }

        foreach ($this->package->config('mime.media') as $mediaType => $mimes) {
            if (in_array($mime, $mimes)) {
                return $mediaType;
            }
        }

        return 'dir';
    }

    /**
     * Get 'thumb' size thumbnail url.
     * @param string $size
     * @return string
     * @throws \Exception
     */
    public function getThumbUrl($size = 'thumb')
    {
        $url = $this->package->config('icons.url');
        $icons = $this->package->config('icons.files');

        if (!$this->metadata->isFile()) {
            return $url . $icons['dir'];
        }

        if ($this->isImage()) {
            $thumbs = $this->getThumbsDetails();

            if (!array_key_exists($size, $thumbs)) {
                return $url . $icons['img'];
            }

            return $thumbs[$size]['url'];
        }

        $mime = $this->getMime();
        if (!array_key_exists($mime, $icons)) {
            $message = sprintf('Configuration file is missing for `%s` file type in `icons.files` array', $mime);
            throw new \Exception($message);
        }

        return $url . $icons[$mime];
    }

    /**
     * Get 'xs' size thumbnail url.
     * @return string
     * @throws \Exception
     */
    public function getXsThumbUrl()
    {
        return $this->getThumbUrl('xs');
    }

    /**
     * Generates url to a file.
     * @param null $path
     * @return string
     */
    public function getUrl($path = null)
    {
        $path = $path ?: $this->path;

        if ($this->package->config('public_storage', false)) {
            // If file has public access enabled, we simply can try return storage
            // url to file.
            try {
                $useAbsolute = $this->package->config('absolute_url', false);
                $absolute = $this->storage->url($path);

                if ($useAbsolute) return $absolute;

                $relative = parse_url($absolute, PHP_URL_PATH);

                return '/' . trim($relative, '\\/');
            } catch (\Exception $ex) {
                // Some drivers does not support url method (like ftp), so we
                // simply continue and generate crip url to our controller.
            }
        }

        $service = new UrlService($this->package);
        if ($this->metadata->isFile()) {
            return $service->file($path);
        }

        return $service->folder($path);
    }

    /**
     * Get blob mime.
     * @return int|string
     */
    public function getMime()
    {
        if ($this->metadata->isFile()) {
            $mimes = $this->package->config('mime.types');
            foreach ($mimes as $mime => $mimeValues) {
                $key = collect($mimeValues)->search(function ($mimeValue) {
                    return preg_match($mimeValue, $this->metadata->getMimeType());
                });

                if ($key !== false) {
                    return $mime;
                }
            }

            return 'file';
        }

        return 'dir';
    }

    /**
     * Get thumbs details.
     * @return array
     */
    public function getThumbsDetails()
    {
        if ($this->thumbsDetails === null) {
            $this->setThumbsDetails();
        }

        return $this->thumbsDetails;
    }

    /**
     * Determines is the current blob an image.
     * @return bool
     */
    private function isImage()
    {
        if ($this->metadata->isFile() &&
            mb_strpos($this->metadata->getMimeType(), 'image/') === 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set thumb sizes details for current file.
     */
    private function setThumbsDetails()
    {
        $this->thumbsDetails = [];
        if ($this->isImage()) {
            $service = new ThumbService($this->package);
            collect($service->getSizes())->each(function ($size, $key) {
                $this->thumbsDetails[$key] = [
                    'size' => $key,
                    'width' => $size[0],
                    'height' => $size[1],
                    'url' => $this->getUrl($key . '/' . $this->path)
                ];
            });
        }
    }

    /**
     * @param string $path
     * @param string $userFolder
     * @return string
     */
    private function prefixPath(string $path, string $userFolder): string
    {
        $thumbService = new ThumbService($this->package);
        $thumbSize = '';

        $isThumb = $thumbService->getSizes()
            ->keys()
            ->filter(function ($size) use ($path, &$thumbSize) {
                $isUsed = starts_with($path, $size);

                if ($isUsed) {
                    $thumbSize = $size;
                }

                return $isUsed;
            })
            ->count();

        if ($isThumb) {
            $relative = str_replace_first($thumbSize, '', $path);

            if (!starts_with(Str::normalizePath($relative), $userFolder)) {
                $relative = $userFolder . '/' . $relative;
            }

            $newPath = $thumbSize . '/' . $relative;

            return Str::normalizePath($newPath);
        }

        return $userFolder . '/' . $path;
    }

    /**
     * @return int
     */
    public function lastModified(): int
    {
        return $this->metadata->getLastModified();
    }
}
