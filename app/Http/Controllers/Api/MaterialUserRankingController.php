<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreMaterialUserRankingRequest;
use App\Models\Material;
use App\Services\MaterialHandling\MaterialUserRankingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MaterialUserRankingController extends Controller {
	public function __construct(private MaterialUserRankingService $rankings) {
	}

	public function update(StoreMaterialUserRankingRequest $request, Material $material): array {
		$material = $this->rankings->set($material, $request->user(), $request->integer('rating'));

		return $this->response($material, $request);
	}

	private function response(Material $material, Request $request): array {
		$this->rankings->present($material, $request->user());

		return $material->only(['rating', 'user_rating', 'user_rating_updated_at']);
	}

	public function destroy(Request $request, Material $material): array {
		$material = $this->rankings->remove($material, $request->user());

		return $this->response($material, $request);
	}
}
