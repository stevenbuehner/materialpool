<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialUserRankingRequest extends FormRequest {
	public function authorize(): bool {
		return TRUE;
	}

	public function rules(): array {
		return [
			'rating' => ['required', 'integer', 'between:0,20'],
		];
	}
}
