<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use App\Support\Authorization\SystemPermissions;
use App\Services\Bundles\BundlePermissionService;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Parental\HasChildren;

/**
 * Class Resource
 *
 * @package App
 * @property int $id
 * @property int $created_by
 * @property string $remote_path
 * @property string $local_path
 * @property string $content_hash
 * @property int|null $filesize
 * @property string $notes
 * @property string $type
 * @property bool $is_public
 * @property            $created_at
 * @property            $updated_at
 * @property User $creator
 * @property Collection $materials
 * @property Collection $foreignIds
 */
class Resource extends Model {
	use HasFactory;
	use HasChildren;

	static           $allResourceTypeKeys   = ['res', 'link', 'file', 'text', 'book', 'audio', 'video', 'image', 'doc'];
	protected static $singleTableType       = 'res';
	protected $childTypes = [
		'res'   => Resource::class,
		'link'  => Url::class,
		'file'  => File::class,
		'audio' => AudioFile::class,
		'video' => VideoFile::class,
		'image' => ImageFile::class,
		'doc'   => DocumentFile::class,
		'pdf'   => PdfFile::class,
		'text'  => Text::class,
		'book'  => Book::class,
	];

	protected $additionalEditViews = [];
	protected $table               = 'resources';
	protected $casts               = [
		'is_public' => 'boolean',
		'options'   => 'array',
		'filesize'  => 'integer',
		// 'created_at' => 'Date'
	];
	protected $guarded             = [
		'id', 'created_by', 'options', 'content_hash', 'filesize', 'type', 'created_at', 'updated_at'
	];

	protected $fillable = [
		'remote_path', 'notes', 'is_public'
	];

	protected $hidden = ['options', 'local_path'];

	#[Scope]
	protected function visibleTo(Builder $query, User $user): void {
		if (!$user->isActive()) {
			$query->whereRaw('1 = 0');

			return;
		}

		if ($user->isSuperAdmin() || $user->can(SystemPermissions::RESOURCES_VIEW_ALL)) {
			return;
		}

		$readableBundleIds = app(BundlePermissionService::class)->readableBundleIds($user);

		$query->where(function (Builder $query) use ($user, $readableBundleIds): void {
			$query->whereHas('foreignIds', fn(Builder $foreignIds) => $foreignIds
				->whereNotNull('bundle_id')
				->whereIn('bundle_id', $readableBundleIds)
			)->orWhere(function (Builder $query) use ($user): void {
				$query->whereDoesntHave('foreignIds', fn(Builder $foreignIds) => $foreignIds->whereNotNull('bundle_id'))
					->where(fn(Builder $query) => $query
						->where('resources.created_by', $user->id)
						->orWhere('resources.is_public', true)
					);
			});
		});
	}

	public function __construct(array $attributes = []) {
		$this->options   = [];
		$this->is_public = FALSE;
		$this->notes     = '';

		parent::__construct($attributes);
	}

	protected static function booted(): void {
		static::creating(function (Resource $resource): void {
			$resource->setAttribute('type', static::$singleTableType);
		});
	}

	public static function getSingleTableTypeMap(): array {
		return (new self())->getChildTypes();
	}

	public static function getSingleTableClass($key) {
		$map = self::getSingleTableTypeMap();

		return isset($map[$key]) ? $map[$key] : NULL;
	}

	public static function getValidationRules() {
		return [
			'is_public'   => 'boolean|nullable',
			'remote_path' => 'nullable|url',
			'notes'       => 'nullable|string'
		];

		// Type, local_path, content_hash, options, file dürfen nicht berücksichtigt werden ... das sind keine Daten, die gesetzt werden sollen an dieser Stelle
		// 'type'=> 'in:' . join(',', array_keys(self::getSingleTableTypeMap())),
	}

	public function toArray() {
		$attributes = $this->attributesToArray();
		$attributes = array_merge($attributes, $this->relationsToArray());

		if (isset($attributes['pivot']['material_id'])) {
			// I need that stuff in vuejs
			// unset($attributes['pivot']['material_id']);
		}

		if (isset($attributes['pivot']['resource_id'])) {
			// I need that stuff in vuejs
			// unset($attributes['pivot']['resource_id']);
		}

		return $attributes;
	}

	/**
	 * @return HandlerInterface[]
	 */
	public function getTagExtractionClasses() {
		return [
		];
	}

	public function creator() {
		return $this->belongsTo(User::class, 'created_by');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function foreignIds() {
		return $this->hasMany(ForeignResourceId::class, 'resource_id');
	}

	/**
	 * Returns an array of additional EditViews that will be loaded on edit (by ResourceController)
	 *
	 * @return array
	 */
	public function getAdditionalEditViews() {
		return $this->additionalEditViews;
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(NoPreviewGenerator::class);
	}

	public function delete() {
		$this->materials()->detach();

		return parent::delete(); // TODO: Change the autogenerated stub
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function materials() {
		return $this->belongsToMany(Material::class, 'material_resource', 'resource_id', 'material_id')
			->withPivot('limitation')
			->using(MaterialResource::class);
	}

	/**
	 * @param $key string
	 * @return bool
	 */
	protected function hasOption(string $key) {
		$options = $this->getAttribute('options');

		return isset($options[$key]);
	}

	/**
	 * @param $key string
	 * @param $value mixed
	 */
	protected function setOption(string $key, $value) {
		$options       = $this->getAttribute('options');
		$options[$key] = $value;
		$this->setAttribute('options', $options);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed|null
	 */
	protected function getOption(string $key, $default = NULL) {
		$options = $this->getAttribute('options');

		if (isset($options[$key])) {
			return $options[$key];
		}

		return $default;
	}

	protected function getTypeAttribute() {
		return static::$singleTableType;
	}

	/**
	 * @param string $key
	 */
	protected function removeOption(string $key) {
		$options = $this->getAttribute('options');

		if (isset($options[$key])) {
			unset($options[$key]);
			$this->setAttribute('options', $options);
		}
	}

}
