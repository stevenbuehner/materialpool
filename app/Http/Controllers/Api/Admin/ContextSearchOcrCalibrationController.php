<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchOcrCalibrationPage;
use App\Models\ContextSearchOcrCalibrationRun;
use App\Services\ContextSearch\OcrCalibrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ContextSearchOcrCalibrationController extends Controller {
	public function index(): array {
		abort_if(app()->isProduction(), 404);
		return [
			'datasets' => ContextSearchEvaluationDataset::query()->where('purpose', 'ocr')->where('status', 'frozen')->orderByDesc('frozen_at')->get(['id', 'title', 'resource_count', 'includes_private']),
			'runs'     => ContextSearchOcrCalibrationRun::query()->withCount('pages')->latest()->limit(30)->get(),
		];
	}

	public function store(Request $request, OcrCalibrationService $calibration): JsonResponse|array {
		abort_if(app()->isProduction(), 404);
		$data = $request->validate([
			'dataset_id'   => ['required', 'uuid', 'exists:context_search_evaluation_datasets,id'],
			'sample_limit' => ['required', 'integer', 'min:15', 'max:500'],
			'title'        => ['nullable', 'string', 'max:255'],
		]);
		try {
			$run = $calibration->start($data['dataset_id'], $request->user(), $data['sample_limit'], $data['title'] ?? NULL);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], 422);
		}
		return ['run' => $this->serializeRun($run)];
	}

	private function serializeRun(ContextSearchOcrCalibrationRun $run): array {
		$run->loadMissing('pages');
		return [
			'id'              => $run->getKey(), 'dataset_id' => $run->dataset_id, 'title' => $run->title, 'status' => $run->status,
			'random_seed'     => $run->random_seed, 'sample_limit' => $run->sample_limit, 'total_pages' => $run->total_pages,
			'processed_pages' => $run->processed_pages, 'reviewed_pages' => $run->reviewed_pages, 'ocr_profile' => $run->ocr_profile,
			'sweep_results'   => $run->sweep_results, 'approved_profile' => $run->approved_profile, 'approved_profile_hash' => $run->approved_profile_hash,
			'pages'           => $run->pages->map(fn(ContextSearchOcrCalibrationPage $page): array => [
				'id'             => $page->id, 'resource_id' => $page->resource_id, 'page_number' => $page->page_number, 'split' => $page->split,
				'status'         => $page->status, 'metrics' => $page->metrics, 'ocr_text' => $page->ocr_text,
				'reference_text' => $page->reference_text, 'quality_label' => $page->quality_label, 'review_note' => $page->review_note,
			])->values(),
		];
	}

	public function show(ContextSearchOcrCalibrationRun $run): array {
		abort_if(app()->isProduction(), 404);
		return ['run' => $this->serializeRun($run->fresh('pages'))];
	}

	public function review(ContextSearchOcrCalibrationRun $run, ContextSearchOcrCalibrationPage $page, Request $request, OcrCalibrationService $calibration): JsonResponse|array {
		abort_if(app()->isProduction(), 404);
		abort_unless((string)$page->run_id === (string)$run->getKey(), 404);
		$data = $request->validate([
			'quality_label'  => ['required', 'string', 'in:usable,unusable,uncertain,handwriting,blank'],
			'reference_text' => ['nullable', 'string', 'max:30000'],
			'review_note'    => ['nullable', 'string', 'max:2000'],
		]);
		try {
			$calibration->review($page, $request->user(), $data['quality_label'], $data['reference_text'] ?? NULL, $data['review_note'] ?? NULL);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], 422);
		}
		return ['run' => $this->serializeRun($run->fresh('pages'))];
	}

	public function evaluate(ContextSearchOcrCalibrationRun $run, OcrCalibrationService $calibration): JsonResponse|array {
		abort_if(app()->isProduction(), 404);
		try {
			return ['results' => $calibration->evaluate($run)];
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], 422);
		}
	}

	public function approve(ContextSearchOcrCalibrationRun $run, Request $request, OcrCalibrationService $calibration): JsonResponse|array {
		abort_if(app()->isProduction(), 404);
		try {
			return ['approved' => $calibration->approve($run, $request->user())];
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], 422);
		}
	}
}
