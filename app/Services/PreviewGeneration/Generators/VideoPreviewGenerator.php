<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;

use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use App\Models\VideoFile;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\ResourceHandling\FileHandlingService;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class VideoPreviewGenerator implements PreviewGeneratorInterface {

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
	 * @throws \League\Flysystem\FileExistsException
	 * @throws \League\Flysystem\FileNotFoundException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $seconds = NULL) {

		$image  = NULL;
		$ffmpeg = FFMpeg::create([
			'ffmpeg.binaries'  => resource_path('bin/ffmpeg'),
			'ffprobe.binaries' => resource_path('bin/ffprobe'),
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
				// Die Datei hat KEINEN Video-Stream - ungewöhnlich, aber möglich (z.B. nur Audio-Streams)
				$duration = (float)$firstVideoStream->get('duration');

				if ($seconds === NULL || $seconds < 0 || $seconds > $duration) {
					$offset = TimeCode::fromSeconds(round($duration * 0.15, 2));
				} else {
					$offset = TimeCode::fromSeconds($seconds);
				}

				$frame     = $video->frame($offset);
				$framePath = $localPath . '.jpg';
				$frame->save($framePath);

				$frameImage = $this->imageManager->make($framePath);
				unlink($framePath);

			} else if ($video->getStreams()->audios()->first() !== NULL) {
				// Es gibt aber wenigstens einen Audio-Stream => Nimm ein Standard-Audio Icon
				$frameImage = $this->imageManager->make(resource_path('icons/resources/headphones.png'));
			} else {
				$frameImage = $this->imageManager->make(resource_path('icons/resources/camera.png'));
			}

			$image = $frameImage->resize($size->getWidth(), $size->getHeight(), function (Constraint $constraint) {
				$constraint->aspectRatio();
				$constraint->upsize();
			});

		} catch (\Exception $e) {
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

		/** @var $resource Text */
		$view = View::make('resources.generators.video')
			->with('resource', $resource)
			->with('context', $context)
			->with('limitation', $limitation)
			->with('content', $resource->content);

		return $view->render();
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return $this->imagePreviewAble($resource);
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return ($resource instanceof VideoFile && $resource->hasLocalFile() && $resource->localFileExists());
	}
}
