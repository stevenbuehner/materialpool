<?php
/**
 * This file was created by  steven
 * Created: 04.03.17 22:47
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\Requests;


use Backpack\CRUD\app\Http\Requests\CrudRequest;

class KeywordCrudRequest extends CrudRequest {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		// only allow updates if the user is logged in
		return \Auth::check();
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'title' => 'required|min:3|max:255',
			'type' => 'required'
		];
	}
}
