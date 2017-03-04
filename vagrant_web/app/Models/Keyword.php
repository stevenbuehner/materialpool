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
	protected static $singleTableSubClasses = [Person::class, Place::class, Language::class];
	public           $timestamps            = TRUE;
	// protected $primaryKey = 'id';
	// protected $guarded = [];
	// protected $hidden = ['id'];
	protected $table    = 'keywords';
	protected $fillable = ['title', 'type'];

	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/

	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/

	public function materials() {
		return $this->belongsToMany(Material::class, 'material_id', 'keyword_material', 'keyword_id');
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
}
