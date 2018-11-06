<?php

namespace App\Models;

use App\Models\Exceptions\InvalidKeywordTypeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

/**
 * Class Keyword
 *
 * @property int        $id
 * @property string     $title
 * @property string     $type
 * @property string     $lc_title
 * @property int        $parent_id
 * @property string     $custom_icon
 * @property Collection $materials
 */
class Keyword extends Model {
	use NodeTrait;

	const AVAILABLE_TYPES = [
		'key'    => [
			'defaultIcon' => '/img/icons/tag.svg'
		],
		'place'  => [
			'defaultIcon' => '/img/icons/place.svg'
		],
		'person' => [
			'defaultIcon' => '/img/icons/person.svg'
		],
		'lang'   => [
			'defaultIcon' => '/img/icons/tag.svg'
		]
	];

	/*
	|--------------------------------------------------------------------------
	| GLOBAL VARIABLES
	|--------------------------------------------------------------------------
	*/
	public static $defaultRelevance = 100;
	public        $timestamps       = TRUE;
	protected     $table            = 'keywords';
	protected     $fillable         = ['title', 'type'];
	protected     $guarded          = ['lc_title'];
	protected     $hidden           = [
		'_lft', '_rgt', 'updated_at', 'created_at'
	];

	protected $appends = [
		'icon'
	];

	public function __construct(array $attributes = []) {
		// Default values
		if (!isset($attributes['type'])) {
			$attributes['type'] = 'key';
		}
		parent::__construct($attributes);
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/

	public static function firstOrCreatePerson($name) {
		return self::firstOrCreate(
			['title' => $name, 'type' => 'person']
		);
	}

	public static function firstOrCreatePlace($name) {
		return self::firstOrCreate(
			['title' => $name, 'type' => 'place']
		);
	}

	public static function firstOrCreateLang($name) {
		return self::firstOrCreate(
			['title' => $name, 'type' => 'lang']
		);
	}


	/**
	 * @param string $value
	 * @param string $type
	 * @param array  $otherAttributes
	 * @return Keyword
	 * @throws InvalidKeywordTypeException
	 */
	public static function create(string $value, string $type = 'key', $otherAttributes = []) {
		$instance = self::make(trim($value), $type, $otherAttributes);

		if ($instance->exists === FALSE) {
			$instance->save();
		}

		return $instance;
	}

	/**
	 * @param string $value
	 * @param string $type
	 * @param array  $otherAttributes
	 * @return Keyword
	 * @throws InvalidKeywordTypeException
	 */
	public static function make(string $value, string $type = 'key', $otherAttributes = []) {

		if (!in_array($type, array_keys(self::AVAILABLE_TYPES))) {
			throw new InvalidKeywordTypeException();
		} else {
			$otherAttributes['type'] = $type;
		}

		return self::firstOrNew(array_merge($otherAttributes, ['title' => trim($value)]));
	}

	public static function searchQuery($text) {
		//		$builder = (new static())->newQueryWithoutScopes();
		$builder = (new self)->newQuery();

		/** @var Builder $builder */
		return $builder->where('title', 'like', '%' . $text . '%');
	}


	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/

	public function toArray() {
		$attributes = $this->attributesToArray();
		$attributes = array_merge($attributes, $this->relationsToArray());

		if (isset($attributes['pivot']['material_id'])) {
			unset($attributes['pivot']['material_id']);
		}

		if (isset($attributes['pivot']['keyword_id'])) {
			unset($attributes['pivot']['keyword_id']);
		}

		return $attributes;
	}

	public function materials() {
		return $this->belongsToMany(Material::class, 'keyword_material', 'keyword_id', 'material_id')
					->withPivot('relevance')
					->using(MaterialKeyword::class);
	}

	/*
	|--------------------------------------------------------------------------
	| ACCESORS
	|--------------------------------------------------------------------------
	*/

	/**
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function descendantMaterials() {

		$query = Material::query();
		$query->select('materials.*')->distinct()->from($this->getTable())
			  ->whereBetween(self::getLftName(), [$this->getLft(), $this->getRgt()])
			  ->join('keyword_material', 'keyword_material.keyword_id', '=', $this->getTable() . '.id')
			  ->join('materials', 'keyword_material.material_id', '=', 'materials.id');

		return $query;
	}

	public function getIconAttribute() {
		$icon = $this->getAttribute('custom_icon');

		if (empty($icon)) {
			$icon = self::AVAILABLE_TYPES[$this->type]['defaultIcon'];
		}

		return $icon;
	}

	public function setIconAttribute($value) {
		$this->setAttribute('custom_icon', $value);
	}

	/*
	|--------------------------------------------------------------------------
	| MUTATORS
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param string $value
	 */
	public function setTitleAttribute(string $value) {
		$value                     = trim($value);
		$this->attributes['title'] = $value;
		$this->updateLcTitle();
	}

	protected function updateLcTitle() {
		$this->attributes['lc_title'] = $this->type . '_' . str_replace(' ', '_', trim(strtolower($this->title)));
	}

	/**
	 * @param string $type
	 * @throws InvalidKeywordTypeException
	 */
	public function setTypeAttribute(string $type) {

		if (!in_array($type, array_keys(self::AVAILABLE_TYPES))) {
			throw new InvalidKeywordTypeException();
		} else {
			$this->attributes['type'] = $type;
			$this->updateLcTitle();
		}
	}

	public function setCustomIconAttribute($value) {

		$attribute_name   = "custom_icon";
		$disk             = "public";
		$destination_path = "keywords/icons";

		// if the image was erased
		if ($value == NULL) {
			// delete the image from disk
			\Storage::disk($disk)->delete($this->image);

			// set null in the database column
			$this->attributes[$attribute_name] = NULL;
		}

		// if a base64 was sent, store it in the db
		if (starts_with($value, 'data:image')) {
			// 0. Make the image
			$image = \Image::make($value);
			// 1. Generate a filename.
			$filename = md5($value . time()) . '.jpg';
			// 2. Store the image on disk.
			\Storage::disk($disk)->put($destination_path . '/' . $filename, $image->stream());
			// 3. Save the path to the database
			$this->attributes[$attribute_name] = $destination_path . '/' . $filename;
		}

	}

}
