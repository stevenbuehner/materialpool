<?php

namespace App\Http\Requests;

use App\Models\Keyword;
use Illuminate\Foundation\Http\FormRequest;

class KeywordRequest extends FormRequest {
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
			'title' => 'bail|required|string|min:3|max:255',
			'type'  => 'bail|nullable|string|in:' . join(',', array_keys(Keyword::getSingleTableTypeMap())),
		];
	}

}
