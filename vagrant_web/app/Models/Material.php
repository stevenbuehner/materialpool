<?php

namespace App\Models;

use App\ResourceLimitations\ResourceLimitationInterface;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Material
 *
 * @package App\Models
 * @property object  $limitation
 * @property string  $title
 * @property string  $description
 * @property int     $rating (0-20)
 * @property boolean $from_bot
 * @property int     $created_by
 * @property int     $modified_by
 */
class Material extends Model {

	use CrudTrait;

	protected $casts = [
		'from_bot'    => 'boolean',
		'description' => 'string',
		'limitation'  => 'object'
	];

	protected $attributes = [
		'rating'      => NULL,
		'description' => '',
		'from_bot'    => FALSE
	];

	protected $fillable = [
		'title', 'description', 'limitation', 'rating', 'from_bot'
	];

	protected $guarded = [
		'id', 'created_by', 'modified_by', 'created_at', 'updated_at'
	];

	protected $hidden = [
		'author_id'
	];

	public function resources() {
		return $this->belongsToMany(Resource::class, 'material_resource', 'material_id', 'resource_id');
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

	public function setLimitationAttribute(ResourceLimitationInterface $limitation = NULL) {
		$this->attributes['limitation'] = serialize($limitation);
	}

	public function getLimitationAttribute($value) {
		return unserialize($value);
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
