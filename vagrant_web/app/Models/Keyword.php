<?php

namespace App\Models;

use App\Models\Exceptions\InvalidKeywordTypeException;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

/**
 * Class Keyword
 *
 * @property string $title
 * @property string $type
 * @property string $lc_title
 * @property int    parent_id
 */
class Keyword extends Model {
	use NodeTrait;
	use SingleTableInheritanceTrait;
	use CrudTrait;

	/*
	|--------------------------------------------------------------------------
	| GLOBAL VARIABLES
	|--------------------------------------------------------------------------
	*/
	public static    $defaultRelevance      = 100;
	protected static $singleTableTypeField  = 'type';
	protected static $singleTableType       = 'key';
	protected static $singleTableSubclasses = [Person::class, Place::class, Language::class, Tag::class];
	public           $timestamps            = TRUE;
	// protected $primaryKey = 'id';
	// protected $guarded = [];
	// protected $hidden = ['id'];
	protected $table    = 'keywords';
	protected $fillable = ['title'];
	protected $guarded  = ['type', 'lc_title'];

	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/

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

		$class = $map[$type];

		return $class::firstOrNew(array_merge($otherAttributes, ['title' => $value]));
	}

	public function getRouteKeyName() {
		return 'lc_title';
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
					->withPivot('relevance');
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

	/*
	|--------------------------------------------------------------------------
	| MUTATORS
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param string $value
	 */
	public function setTitleAttribute(string $value) {
		$this->attributes['title']    = $value;
		$this->attributes['lc_title'] = str_replace(' ', '_', trim(strtolower($value)));
	}
}
