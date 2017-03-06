<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

/**
 * Class Resource
 *
 * @package App
 * @property string $content_hash
 * @property string $path
 * @property string $notes
 */
class Resource extends Model {
	use SingleTableInheritanceTrait;

	static           $allResourceTypeKeys   = ['res', 'link', 'file', 'text', 'book', 'audio', 'video', 'image', 'doc'];
	protected static $singleTableTypeField  = 'type';
	protected static $singleTableSubclasses = [Url::class, File::class, Text::class, Book::class];
	protected static $singleTableType       = 'res';
	protected        $table                 = 'resources';
	protected        $casts                 = [
		'is_public' => 'boolean',
		'options'   => 'array'
	];

	public function __construct(array $attributes = []) {
		$this->options   = [];
		$this->is_public = FALSE;
		$this->notes     = '';

		parent::__construct($attributes);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function materials() {
		return $this->belongsToMany(Material::class, 'material_resource', 'resource_id', 'material_id');
	}


}
