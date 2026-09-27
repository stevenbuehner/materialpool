<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUserRanking extends Model {
	use HasFactory;

	protected $table = 'material_user_ranking';

	protected $fillable = ['rating'];

	protected function casts(): array {
		return ['rating' => 'integer'];
	}

	public function material(): BelongsTo {
		return $this->belongsTo(Material::class);
	}

	public function user(): BelongsTo {
		return $this->belongsTo(User::class);
	}
}
