<?php
/**
 * This file was created by  steven
 * Created: 04.03.17 22:42
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\Controllers\Admin;


use App\Http\Requests\KeywordCrudRequest as StoreRequest;
use App\Http\Requests\KeywordCrudRequest as UpdateRequest;
use App\Models\Keyword;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class KeywordCrudController extends CrudController {

	public function setup() {
		$this->crud->setModel(Keyword::class);
		$this->crud->setRoute('admin/keyword');
		$this->crud->setEntityNameStrings('keyword', 'keywords');

		$this->crud->enableReorder('title', 5);
		$this->crud->allowAccess('reorder');

		$this->crud->setColumns(['title', 'type']);
		$this->crud->addField(
			[
				'name'  => 'title',
				'label' => 'Name',
				'type'  => 'text'
			]);

		$typeNames = Keyword::getSingleTableTypeMap();
		foreach ($typeNames as $key => $value) {
			$t               = preg_split('~\\\\~', $value);
			$typeNames[$key] = array_pop($t);
		}

		$this->crud->addField(
			[ // select_from_array
			  'name'        => 'type',
			  'label'       => "Tag Type",
			  'type'        => 'select_from_array',
			  'options'     => $typeNames,
			  'allows_null' => FALSE,
			  // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
			]);

		$this->crud->addField(
			[ // select_from_array
			  'label'        => "Eigenes Bild",
			  'name'         => "custom_icon",
			  'type'         => 'image',
			  'upload'       => TRUE,
			  'crop'         => TRUE, // set to true to allow cropping, false to disable
			  'aspect_ratio' => 1, // ommit or set to 0 to allow any aspect ratio,
			  'allows_null'  => TRUE
			]);

	}

	public function store(StoreRequest $request) {
		return parent::storeCrud();
	}

	public function update(UpdateRequest $request) {
		return parent::updateCrud();
	}


}