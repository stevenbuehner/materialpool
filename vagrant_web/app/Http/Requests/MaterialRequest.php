<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest {
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		return TRUE;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'title'       => 'bail|string|min:3|max:255',
			'description' => 'bail|nullable|string',
			'rating'      => 'bail|nullable|integer|between:0,20',
			'flag'        => 'bail|nullable|integer|min:0',
			'created_at'  => 'bail:'
		];
	}

}
