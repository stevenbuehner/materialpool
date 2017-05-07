<?php

namespace App\Models;

class DocumentFile extends File {

	protected static $singleTableType = 'doc';


	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file|mimes:doc,docx,pdf';

		return $rules;
	}

}
