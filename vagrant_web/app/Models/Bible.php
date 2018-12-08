<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bible
 *
 * @package App\Models
 * @property int       $id
 * @property string    $uuid
 * @property string    $title
 * @property string    $decription
 * @property \DateTime $ersion_date
 * @property string    creator
 * @property string    language
 * @property string    rights
 * @property string    source
 */
class Bible extends Model {
	use HasTimestamps;

	protected $fillable = [
		'uuid', 'title', 'description', 'version_date', 'creator', 'language', 'rights', 'source'
	];

	protected $casts = [
		'version_date' => 'datetime',
	];

	public function verses() {
		return $this->hasMany(BibleContent::class);
	}

}
