<?php

namespace App\Services\MaterialHandling;

use App\Events\MaterialWasChanged;
use App\Models\Material;
use App\Models\MaterialUserRanking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MaterialUserRankingService {
	public function set(Material $material, User $user, int $rating): Material {
		[$material, $defaultChanged] = DB::transaction(function () use ($material, $user, $rating): array {
			$lockedMaterial = $this->lockMaterial($material->getKey());
			$ranking = MaterialUserRanking::query()
				->where('material_id', $lockedMaterial->id)
				->where('user_id', $user->id)
				->first();

			if ($ranking === null) {
				$ranking = new MaterialUserRanking();
				$ranking->material_id = $lockedMaterial->id;
				$ranking->user_id = $user->id;
				$ranking->rating = $rating;
			} else {
				$ranking->rating = $rating;
			}
			$ranking->save();

			return [$lockedMaterial, $this->recalculateLocked($lockedMaterial)];
		});

		if ($defaultChanged) {
			event(new MaterialWasChanged($material));
		}

		return $material;
	}

	public function remove(Material $material, User $user): Material {
		[$material, $defaultChanged] = DB::transaction(function () use ($material, $user): array {
			$lockedMaterial = $this->lockMaterial($material->getKey());
			MaterialUserRanking::query()
				->where('material_id', $lockedMaterial->id)
				->where('user_id', $user->id)
				->delete();

			return [$lockedMaterial, $this->recalculateLocked($lockedMaterial)];
		});

		if ($defaultChanged) {
			event(new MaterialWasChanged($material));
		}

		return $material;
	}

	/**
	 * Entfernt die Rankings eines final gelöschten Benutzers. Der aufrufende
	 * Löschpfad hält die äußere Transaktion und löst Events erst nach Commit aus.
	 *
	 * @return Collection<int, Material>
	 */
	public function removeForUser(User $user): Collection {
		$materialIds = MaterialUserRanking::query()
			->where('user_id', $user->id)
			->orderBy('material_id')
			->pluck('material_id');
		$changedMaterials = new Collection();

		foreach ($materialIds as $materialId) {
			$material = $this->lockMaterial($materialId);
			MaterialUserRanking::query()
				->where('material_id', $material->id)
				->where('user_id', $user->id)
				->delete();

			if ($this->recalculateLocked($material)) {
				$changedMaterials->push($material);
			}
		}

		return $changedMaterials;
	}

	public function merge(Material $main, Material $second): void {
		$materials = Material::query()
			->whereKey([$main->id, $second->id])
			->orderBy('id')
			->lockForUpdate()
			->get()
			->keyBy('id');
		$main = $materials->get($main->id);
		$second = $materials->get($second->id);

		$secondRankings = MaterialUserRanking::query()
			->where('material_id', $second->id)
			->orderBy('user_id')
			->get();

		foreach ($secondRankings as $secondRanking) {
			$mainRanking = MaterialUserRanking::query()
				->where('material_id', $main->id)
				->where('user_id', $secondRanking->user_id)
				->first();

			if ($mainRanking === null || $secondRanking->updated_at->gt($mainRanking->updated_at)) {
				MaterialUserRanking::query()->updateOrCreate(
					['material_id' => $main->id, 'user_id' => $secondRanking->user_id],
					[
						'rating' => $secondRanking->rating,
						'created_at' => $secondRanking->created_at,
						'updated_at' => $secondRanking->updated_at,
					]
				);
			}
		}

		MaterialUserRanking::query()->where('material_id', $second->id)->delete();
		$this->recalculateLocked($main);
	}

	public function present(Material $material, User $user): Material {
		if (!$material->relationLoaded('userRankings')) {
			$material->load(['userRankings' => fn ($query) => $query->where('user_id', $user->id)]);
		}

		$ranking = $material->userRankings->firstWhere('user_id', $user->id);
		$material->setAttribute('user_rating', $ranking?->rating);
		$material->setAttribute('user_rating_updated_at', $ranking?->updated_at?->toJSON());
		$material->makeHidden('userRankings');

		return $material;
	}

	/** @param Collection<int, Material> $materials */
	public function presentCollection(Collection $materials, User $user): Collection {
		$materials->load(['userRankings' => fn ($query) => $query->where('user_id', $user->id)]);

		return $materials->each(fn (Material $material) => $this->present($material, $user));
	}

	private function lockMaterial(int $materialId): Material {
		return Material::query()->lockForUpdate()->findOrFail($materialId);
	}

	private function recalculateLocked(Material $material): bool {
		$average = MaterialUserRanking::query()->where('material_id', $material->id)->avg('rating');
		if ($average === null) {
			return false;
		}

		$rating = (int) round((float) $average, 0, PHP_ROUND_HALF_UP);
		if ($material->rating !== null && (int) $material->rating === $rating) {
			return false;
		}

		$material->rating = $rating;
		$material->save();

		return true;
	}
}
