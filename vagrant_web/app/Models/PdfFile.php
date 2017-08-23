<?php

namespace App\Models;

class PdfFile extends File {

	protected static $singleTableType = 'pdf';


	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file|mimes:pdf';

		return $rules;
	}


}
