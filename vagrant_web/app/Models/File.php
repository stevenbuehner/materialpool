<?php

namespace App\Models;

use App\Services\TagExtraction\ResourceHandles\FileExifHandler;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Class File
 *
 * @package App\Models
 * @property string|null $original_filename
 */
class File extends Resource {

	protected static $singleTableSubclasses = [AudioFile::class, VideoFile::class, ImageFile::class, DocumentFile::class];
	protected static $singleTableType       = 'file';
	protected static $ORIGINAL_FILENAME     = 'of';

	protected $cachedData = [];

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		$this->appends[]             = 'original_filename';
		$this->fillable[]            = 'original_filename';
		$this->additionalEditViews[] = 'resources.files.edit-partial';
	}

	public static function getValidationRules() {
		$rules                      = parent::getValidationRules();
		$rules['file']              = 'bail|required|file';
		$rules['original_filename'] = 'string';

		return $rules;
	}

	/**
	 * @return HandlerInterface[]
	 */
	public function getTagExtractionClasses() {
		return [
			FileNameHandler::class,
			FileExifHandler::class
		];
	}

	public function setOriginalFilenameAttribute($originalFileName) {
		$this->setOption(self::$ORIGINAL_FILENAME, $originalFileName);
	}


	public function getOriginalFilenameAttribute() {
		return $this->getOption(self::$ORIGINAL_FILENAME, NULL);
	}

	public function getLocalFile() {
		return $this->getLocalDisk()->get($this->getLocalFilePath());
	}

	public function getLocalDisk() {
		list($storage, $path) = $this->getLocalStorageAndPath();

		return Storage::disk($storage);
	}

	public function getLocalStorageAndPath() {
		if (!isset($this->cachedData['storage']) || !isset($this->cachedData['path'])) {
			$local = $this->getAttribute('local_path');
			list($storage, $path) = preg_split('~::~', $local, 2);
			$this->cachedData['storage'] = $storage;
			$this->cachedData['path']    = $path;
		}

		return [
			$this->cachedData['storage'],
			$this->cachedData['path']
		];
	}

	public function getLocalFilePath() {
		list($storage, $path) = $this->getLocalStorageAndPath();

		return $path;
	}

	public function getLocalFileStream() {
		$driver = $this->getLocalDisk()->getDriver();
		return $driver->readStream($this->getLocalFilePath());
	}

	public function deleteLocalFile() {
		try {
			$result = $this->getLocalDisk()->delete($this->getLocalFilePath());
		} catch (FileNotFoundException $e) {
			$result = FALSE;
		}

		$this->setAttribute('local_path', NULL);
		$this->setAttribute('original_filename', '');

		return $result;
	}

	public function getLocalMimeType() {
		return $this->getLocalDisk()->mimeType($this->getLocalFilePath());
	}

	public function getLocalUrl() {
		return $this->getLocalDisk()->url($this->getLocalFilePath());
	}

	/**
	 * @return \Illuminate\Filesystem\FilesystemAdapter
	 */
	public function getLocalSize() {
		return $this->getLocalDisk()->size($this->getLocalFilePath());
	}

	/**
	 * @return bool
	 */
	public function hasLocalFile() {
		return !empty($this->getAttribute('local_path'));
	}

	public function localFileExists() {
		$disk = $this->getLocalDisk();
		$path = $this->getLocalFilePath();

		return $disk->exists($path);
	}

	/**
	 * @return bool
	 */
	public function hasRemoteFile() {
		return !empty($this->getAttribute('remote_path'));
	}

}
