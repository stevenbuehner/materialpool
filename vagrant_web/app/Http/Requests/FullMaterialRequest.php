<?php

namespace App\Http\Requests;

use App\Models\Keyword;
use Illuminate\Foundation\Http\FormRequest;

class FullMaterialRequest extends FormRequest {
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
	 * Notice: Also used in ForeignMaterialController
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'title'       => 'bail|required|string|min:3|max:255',
			'rating'      => 'bail|nullable|numeric|between:0,20',
			'from_bot'    => 'bail|boolean',
			'description' => 'bail|nullable|string',
			'author'      => 'bail|nullable|string|min:2|max:191',

			'keywords.*.title'     => 'bail|required|string|min:2|max:191',
			'keywords.*.type'      => 'bail|string|in:' . $this->getKeywordTypes(),
			'keywords.*.relevance' => 'bail|nullable|numeric|between:0,300',

			'bibleverses.*.from'      => 'bail|required|numeric|digits_between:7,9',
			'bibleverses.*.to'        => 'bail|required|numeric|digits_between:7,9',
			'bibleverses.*.bible_id'  => 'bail|nullable|numeric|between:1,999',
			'bibleverses.*.relevance' => 'bail|nullable|numeric|between:0,300',
		];
	}

	protected function getKeywordTypes() {
		return join(',', array_keys(Keyword::getSingleTableTypeMap()));
	}

}
