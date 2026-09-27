<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterialUsageRequest extends FormRequest {
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
			// 'material_id' => ['required', 'integer', 'exists:App\Models\Material,id'],
			'used_by_id' => ['bail', 'nullable', 'integer', 'exists:App\Models\User,id'],
			'datetime'   => ['bail', 'required', 'date'],
			'place'      => ['bail', 'nullable', 'min:0', 'max:191'],
			'reason'     => ['bail', 'nullable', 'min:0', 'max:191'],
		];
	}

	public function messages() {
		$messages                      = parent::messages();
		$messages['used_by_id.exists'] = 'The selected used_by_id is invalid';

		return $messages;
	}

}
