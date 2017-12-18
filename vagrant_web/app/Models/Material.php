<?php

namespace App\Models;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Class Material
 *
 * @package App\Models
 * @property int     $id
 * @property string  $title
 * @property string  $description
 * @property int     $rating (0-20)
 * @property boolean $from_bot
 * @property int     $created_by
 * @property int     $modified_by
 * @property int     $author_id
 */
class Material extends Model {

	use CrudTrait;

	public const MAX_RATING = 20;

	protected $casts = [
		'from_bot'    => 'boolean',
		'description' => 'string',
	];

	protected $attributes = [
		'rating'      => NULL,
		'description' => '',
		'from_bot'    => FALSE
	];

	protected $fillable = [
		'title', 'description', 'rating', 'from_bot'
	];

	protected $guarded = [
		'id', 'created_by', 'modified_by', 'created_at', 'updated_at'
	];

	protected $hidden = [
		'author_id'
	];

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
	}

	public function foreignResources($userId) {

		$fi = new        ForeignResourceId();

		$query = DB::table($fi->getTable())
				   ->where('user_id', $userId)
				   ->join('resources', $fi->getTable() . '.resource_id', 'resources.id')
				   ->join('material_resource', 'resources.id', 'material_resource.resource_id')
				   ->where('material_resource.material_id', $this->id)
				   ->select($fi->getTable() . '.*');

		$builder = $fi->newEloquentBuilder($query); //->with('resource');
		$builder->setModel($fi);
		$builder->with('resource');

		$c = $builder->get();

		return $c;
	}

	public function resources() {
		return $this->belongsToMany(Resource::class, 'material_resource', 'material_id', 'resource_id')
					->withPivot('limitation')
					->using(MaterialResource::class);
	}

	public function keywords() {
		return $this->keyWordClassAndChildren(Keyword::class);
	}

	/**
	 * @param $class
	 * @return BelongsToMany
	 */
	protected function keyWordClassAndChildren($class) {
		return $this->belongsToMany($class, 'keyword_material', 'material_id', 'keyword_id')->withPivot('relevance');
	}

	public function foreignIds() {
		return $this->hasMany(ForeignMaterialId::class);
	}

	public function persons() {
		return $this->keyWordClassAndChildren(Person::class);
	}

	public function languages() {
		return $this->keyWordClassAndChildren(Language::class);
	}

	public function tags() {
		return $this->keyWordClassAndChildren(Tag::class);
	}

	public function places() {
		return $this->keyWordClassAndChildren(Place::class);
	}

	public function creator() {
		return $this->belongsTo(User::class, 'created_by');
	}

	public function modifier() {
		return $this->belongsTo(User::class, 'modified_by');
	}

	/**
	 * @return BelongsToMany
	 */
	public function bibleverses() {
		return $this->belongsToMany(Bibleverse::class)->withPivot('relevance');
	}

	public function author() {
		return $this->belongsTo(Person::class, 'author_id');
	}

	public function setRatingAttribute($value) {
		// Not more than 20!
		$this->attributes['rating'] = min($value, 20);
	}


}
