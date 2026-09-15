<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMaterialsRequest extends FormRequest {
	public function authorize(): bool {
		return $this->user()?->isActive() ?? FALSE;
	}

	public function rules(): array {
		return [
			'q' => 'bail|nullable|array|max:20',
			'q.*' => 'bail|array|max:50',
			'q.*.*' => 'bail|array',
			'q.*.*.type' => 'bail|required|string|in:k,b,t,*',
			'q.*.*.id' => 'bail|required_if:q.*.*.type,k|integer|min:1',
			'q.*.*.from' => 'bail|required_if:q.*.*.type,b|integer|min:1',
			'q.*.*.to' => 'bail|required_if:q.*.*.type,b|integer|min:1',
			'q.*.*.text' => 'bail|required_if:q.*.*.type,t,*|string|max:255',
			'page' => 'bail|nullable|integer|min:1',
			'per_page' => 'bail|nullable|integer|between:1,100',
			'order_by' => 'bail|nullable|in:created_at,updated_at',
		];
	}
}
