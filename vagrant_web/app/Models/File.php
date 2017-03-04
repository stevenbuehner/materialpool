<?php

namespace App\Models;

class File extends Resource {

	protected static $singleTableSubclasses = [AudioFile::class, VideoFile::class, ImageFile::class, DocumentFile::class];
	protected static $singleTableType       = 'file';

}
