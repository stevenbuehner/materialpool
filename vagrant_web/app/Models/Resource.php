<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

/**
 * Class Resource
 *
 * @package App
 * @property int    $id
 * @property int    created_by
 * @property string $remote_path
 * @property string $local_path
 * @property string $content_hash
 * @property string $notes
 */
class Resource extends Model {
	use SingleTableInheritanceTrait;

	static           $allResourceTypeKeys   = ['res', 'link', 'file', 'text', 'book', 'audio', 'video', 'image', 'doc'];
	protected static $singleTableTypeField  = 'type';
	protected static $singleTableSubclasses = [Url::class, File::class, Text::class, Book::class];
	protected static $singleTableType       = 'res';

	protected $table   = 'resources';
	protected $casts   = [
		'is_public' => 'boolean',
		'options'   => 'array'
	];
	protected $guarded = [
		'id', 'created_by', 'options', 'content_hash', 'type', 'created_at', 'updated_at'
	];

	protected $fillable = [
		'remote_path', 'notes', 'is_public'
	];

	protected $hidden = ['options', 'local_path'];

	public function __construct(array $attributes = []) {
		$this->options   = [];
		$this->is_public = FALSE;
		$this->notes     = '';

		parent::__construct($attributes);
	}

	public static function getSingleTableClass($key) {
		$map = self::getSingleTableTypeMap();

		return isset($map[$key]) ? $map[$key] : NULL;
	}

	public static function getValidationRules() {
		return [
			'is_public'   => 'boolean|nullable',
			'remote_path' => 'string|nullable',
			'notes'       => 'string|nullable'
		];

		// Type, local_path, content_hash, options, file dürfen nicht berücksichtigt werden ... das sind keine Daten, die gesetzt werden sollen an dieser Stelle
		// 'type'=> 'in:' . join(',', array_keys(self::getSingleTableTypeMap())),

	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function materials() {
		return $this->belongsToMany(Material::class, 'material_resource', 'resource_id', 'material_id');
	}

	public function foreignResourceKeys() {
		return $this->hasMany(ForeignResourceKey::class);
	}

	/**
	 * @param $key string
	 * @param $value mixed
	 */
	protected function setOption(string $key, $value) {
		$options       = $this->getAttribute('options');
		$options[$key] = $value;
		$this->setAttribute('options', $options);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	protected function getOption(string $key, $default = NULL) {
		$options = $this->getAttribute('options');

		if (isset($options[$key])) {
			return $options[$key];
		}

		return $default;
	}

	/**
	 * @param string $key
	 */
	protected function removeOption(string $key) {
		$options = $this->getAttribute('options');

		if (isset($options[$key])) {
			unset($options[$key]);
			$this->setAttribute('options', $options);
		}
	}

}
