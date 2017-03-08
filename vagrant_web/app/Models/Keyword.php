<?php

namespace App\Models;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

class Keyword extends Model {
	use NodeTrait;
	use SingleTableInheritanceTrait;
	use CrudTrait;

	/*
	|--------------------------------------------------------------------------
	| GLOBAL VARIABLES
	|--------------------------------------------------------------------------
	*/
	protected static $singleTableTypeField  = 'type';
	protected static $singleTableType       = 'key';
	protected static $singleTableSubclasses = [Person::class, Place::class, Language::class, Tag::class];
	public           $timestamps            = TRUE;
	// protected $primaryKey = 'id';
	// protected $guarded = [];
	// protected $hidden = ['id'];
	protected $table    = 'keywords';
	protected $fillable = ['title', 'type', 'parent_id'];

	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/


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
	| RELATIONS
	|--------------------------------------------------------------------------
	*/

	public function materials() {
		return $this->belongsToMany(Material::class, 'keyword_material', 'material_id', 'keyword_id');
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
