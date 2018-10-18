<?php

namespace App\Models;

use App\Models\Exceptions\InvalidKeywordTypeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

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
	use SingleTableInheritanceTrait;

	/*
	|--------------------------------------------------------------------------
	| GLOBAL VARIABLES
	|--------------------------------------------------------------------------
	*/
	public static    $defaultRelevance      = 100;
	protected static $singleTableTypeField  = 'type';
	protected static $singleTableType       = 'key';
	protected static $defaultIcon           = '/img/icons/tag.svg';
	protected static $singleTableSubclasses = [Person::class, Place::class, Language::class];
	public           $timestamps            = TRUE;
	protected        $table                 = 'keywords';
	protected        $fillable              = ['title'];
	protected        $guarded               = ['type', 'lc_title'];
	protected        $hidden                = [
		'_lft', '_rgt', 'updated_at', 'created_at'
	];

	protected $appends = [
		'icon'
	];

	public function __construct(array $attributes = []) {
		// Default values
		$attributes['type'] = $this::$singleTableType;
		parent::__construct($attributes);
	}

	public static function getSingleTableClass($key) {
		$map = self::getSingleTableTypeMap();

		return isset($map[$key]) ? $map[$key] : NULL;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/

	public static function boot() {
		parent::boot();

		static::deleting(function ($obj) {
			if ($obj->custom_image) {
				\Storage::disk('public')->delete($obj->custom_image);
			}
		});
	}

	/**
	 * @param string $value
	 * @param string $type
	 * @param array  $otherAttributes
	 * @return Keyword
	 * @throws InvalidKeywordTypeException
	 */
	public static function create(string $value, string $type = 'key', $otherAttributes = []) {
		$instance = self::make($value, $type, $otherAttributes);

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
		$map = self::getSingleTableTypeMap();

		if (!in_array($type, array_keys(self::getSingleTableTypeMap()))) {
			throw new InvalidKeywordTypeException();
		}

		$class                   = $map[$type];
		$otherAttributes['type'] = $type;

		return $class::firstOrNew(array_merge($otherAttributes, ['title' => $value]));
	}

	public static function getSingleTableType() {
		return static::$singleTableType;
	}

	public static function searchQuery($text) {
		$builder = (new static())->newQueryWithoutScopes();

		/** @var Builder $builder */
		return $builder->where('title', 'like', '%' . $text . '%');
	}

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

	/**
	 * Get the node siblings and the node itself.
	 *
	 * @return \Kalnoy\Nestedset\QueryBuilder
	 */
	public function childrenAndSelf() {
		return $this->newScopedQuery()
					->where($this->getParentIdName(), '=', $this->getParentId());
	}

	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/

	public function materials() {
		return $this->belongsToMany(Material::class, 'keyword_material', 'keyword_id', 'material_id')
					->withPivot('relevance')
					->using(MaterialKeyword::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function descendantMaterials() {

		$query = Material::query();
		$query->select('materials.*')->distinct()->from($this->getTable())
			  ->whereBetween(self::getLftName(), [$this->getLft(), $this->getRgt()])
			  ->whereIn('keywords.type', $this->getSingleTableTypes())
			  ->join('keyword_material', 'keyword_material.keyword_id', '=', $this->getTable() . '.id')
			  ->join('materials', 'keyword_material.material_id', '=', 'materials.id');

		return $query;
	}

	/**
	 * Override this model to make shure, that GLOBAL-Scopes are not applied
	 * (=> SingleTableInheritance would kick in and make separate trees for each type)
	 * Get a new base query that includes deleted nodes.
	 *
	 * @since 1.1
	 *
	 * @return QueryBuilder
	 */
	public function newNestedSetQuery($table = NULL) {
		$builder = $this->usesSoftDelete()
			? $this->withTrashed()
			: $this->newQueryWithoutScopes();

		return $this->applyNestedSetScope($builder, $table);
	}

	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/

	/*
	|--------------------------------------------------------------------------
	| ACCESORS
	|--------------------------------------------------------------------------
	*/

	public function getTypeAttribute() {
		return $this::$singleTableType;
	}

	public function getIconAttribute() {
		$icon = $this->getAttribute('custom_icon');

		if (empty($icon)) {
			$icon = static::$defaultIcon;
		}

		return $icon;
	}

	public function setIconAttribute($value) {
		$this->setAttribute('custom_icon', $value);
	}

	/**
	 * @param string $value
	 */
	public function setTitleAttribute(string $value) {
		$value                        = trim($value);
		$this->attributes['title']    = $value;
		$this->attributes['lc_title'] = self::unifyTitleToLowerCase($value);
	}

	/*
	|--------------------------------------------------------------------------
	| MUTATORS
	|--------------------------------------------------------------------------
	*/

	public static function unifyTitleToLowerCase(string $title) {
		return static::$singleTableType . '_' . str_replace(' ', '_', trim(strtolower($title)));
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

	/**
	 * Set the value of model's parent id key.
	 *
	 * Behind the scenes node is appended to found parent node.
	 *
	 *
	 * OVERRIDE Default Function in NodeTrait (because of complications in usage with SingleTableInheritanceTrait)
	 * @param int $value
	 *
	 * @throws Exception If parent node doesn't exists
	 */
	public function setParentIdAttribute($value) {
		if ($this->getParentId() == $value) {
			return;
		}

		if ($value) {
			$model = (new Keyword())->findOrFail($value);
			$this->appendToNode($model);
		} else {
			$this->makeRoot();
		}
	}
}
