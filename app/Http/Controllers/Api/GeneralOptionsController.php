<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Support\Authorization\SystemPermissions;


class GeneralOptionsController extends BaseController {

	public function __construct() {
		$this->middleware('auth:api');
	}

	public function index() {
		$user = $this->getUserInformation();

		return [
			'user'       => $user,
			'permissions' => $user['permissions'],
			'server'     => [
				'max_upload' => $this->file_upload_max_size()
			],
			'systemname' => config('app.name')
		];

	}

	protected function getUserInformation() {
		$user = Auth::user();
		$data = $user->makeVisible(['frontend_user_settings', 'email', 'is_admin'])->toArray();
		$data['permissions'] = $user->isSuperAdmin()
			? SystemPermissions::all()
			: $user->getAllPermissions()->pluck('name')->sort()->values()->all();

		return $data;
	}

	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size (Drupal-Lösung)

	protected function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = $this->parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {
				$max_size = $post_max_size;
			}

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}

		return $max_size;
	}

	protected function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size);      // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}

}
