<?php

namespace App\Http\Controllers;

use App\Events\ResourceWasChanged;
use App\Models\File;
use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ResourceController extends Controller {

	use ResourceHelperTrait;

	public function __construct() {
		$this->middleware(['auth']);
		$this->middleware('can:create,App\Models\Resource')->only(['create', 'store']);
		$this->middleware('can:create,App\Models\Material')->only(['store']);
		$this->middleware('can:view,resource')->only(['show', 'download']);
		$this->middleware('can:update,resource')->only(['edit', 'update']);
		$this->middleware('can:delete,resource')->only(['destroy']);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$resources = ResourceEntity::query()
			->visibleTo(Auth::user())
			->paginate(20);

		return view('resources.index', compact('resources'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		return view('resources.create');
	}

	/**
	 * Store a newly created resource or resoures (plural!) in storage and create single material for them.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {

		if (!$request->hasFile('file')) {
			return redirect(route('pool.resource.create'))->withErrors(['Missing upload file']);
		}

		$oneMaterial = (bool)$request->get('one_material', FALSE);
		$metaData    = $request->get('meta', '');
		$resources   = $this->handleMultiResourceFileData($request);

		if ($oneMaterial === TRUE) {
			$resources = [$resources];
		}

		foreach ($resources as $resource) {
			$material = $this->createMaterialFromResources($resource, $metaData);
		}


		return view('vuerouter.index', [
			'store' => [
				'materials' => [
					$material->load(MaterialController::withVisibleAttributes(Auth::user())),
				]],
			'route' => ['name' => 'material-detail', 'params' => ['id' => $material->id]]
		]);
	}


	/**
	 * Display the specified resource.
	 *
	 * @param ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function show(ResourceEntity $resource) {
		$resource->load($this->visibleMaterialRelations());

		return view('resources.show')->with('resource', $resource);
	}

	/**
	 * Verknüpfte Materialien dürfen die Sichtbarkeitsprüfung nicht umgehen,
	 * nur weil die Ressource selbst sichtbar ist.
	 */
	protected function visibleMaterialRelations(): array {
		return [
			'materials' => static fn($query) => $query->visibleTo(Auth::user()),
			'materials.keywords',
			'materials.bibleverses',
		];
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function edit(ResourceEntity $resource) {
		$resource->load($this->visibleMaterialRelations());

		return view('resources.edit', [
			'resource' => $resource
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param Request $request
	 * @param ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, ResourceEntity $resource) {

		$resource->fill($request->all());
		$resource->save();
		event(new ResourceWasChanged($resource));

		return redirect(route('pool.resource.edit', $resource->id));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(ResourceEntity $resource) {
		//
	}

	public function download(ResourceEntity $resource) {

		if ($resource instanceof File && $resource->hasLocalFile()) {
			$stream = $resource->getLocalFileStream();

			return Response::stream(function () use ($stream) {
				fpassthru($stream);
			}, 200, [
				'Content-Type'        => $resource->getLocalMimeType(),
				'Content-Length'      => $resource->getLocalSize(),
				'Content-disposition' => "attachment; filename=\"" . $resource->getOriginalFilenameAttribute() . "\""
			]);
		} else if ($resource instanceof Text) {

			$filename = $resource->getOriginalFilenameAttribute();
			$filename = empty($filename) ? "resource id-" . $resource->id . ".txt" : $filename;

			return Response::make($resource->content, 200, [
				'Content-type'        => 'text/plain',
				'Content-Disposition' => "attachment; filename=\"$filename\"",
				// 'Content-Length'      => strlen(utf8_decode($resource->content)) // FIxMe: Not working
			]);
		} else if ($resource->hasRemoteFile()) {
			return redirect()->to($resource->remote_path);
		}

	}
}
