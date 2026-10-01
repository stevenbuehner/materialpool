<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;

use App\Models\AudioFile;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\ResourceHandling\FileHandlingService;
use Exception;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;

class AudioPreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;
	protected $fileHandlingService;

	public function __construct(ImageManager $imageManager, FileHandlingService $fileHandlingService) {
		$this->imageManager        = $imageManager;
		$this->fileHandlingService = $fileHandlingService;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param null $seconds
	 * @return Image
	 * @throws NotPreviewAbleException
	 * @throws FileExistsException
	 * @throws FileNotFoundException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $seconds = NULL) {

		$image  = NULL;
		$ffmpeg = FFMpeg::create([
			'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
			'ffprobe.binaries' => '/usr/bin/ffprobe',
			'timeout'          => 3600, // The timeout for the underlying process
			'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
		]);

		$localPath = NULL;

		try {
			// Make a local copy of the movie (copy to local, whereever it is)
			$localPath = $this->fileHandlingService->makeLocalCopy($resource);

			$video            = $ffmpeg->open($localPath);
			$firstVideoStream = $video->getStreams()->videos()->first();

			if ($firstVideoStream !== NULL) {

				$offset    = TimeCode::fromSeconds(0);
				$frame     = $video->frame($offset);
				$framePath = $localPath . '.jpg';
				$frame->save($framePath);

				$frameImage = $this->imageManager->make($framePath);
				unlink($framePath);

			} else {
				$frameImage = $this->imageManager->make(resource_path('icons/resources/headphones.png'));
			}

			$image = $frameImage->resize($size->getWidth(), $size->getHeight(), function (Constraint $constraint) {
				$constraint->aspectRatio();
				$constraint->upsize();
			});

		} catch (Exception $e) {
		} finally {
			// Cleanup
			if ($localPath !== NULL) {
				$this->fileHandlingService->cleanupLocalCopy($localPath);
			}
		}

		// Backup
		if ($image === NULL) {
			throw new NotPreviewAbleException('no Preview available');
		}

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL) {
		return FALSE;
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return FALSE;
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return ($resource instanceof AudioFile && $resource->hasLocalFile() && $resource->localFileExists());
	}
}
