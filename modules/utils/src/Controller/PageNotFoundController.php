<?php

namespace Drupal\gv_fanatics_plus_utils\Controller;

/**
 * Controlador de página no encontrada
 */
class PageNotFoundController {
	
	public function show() {
		
		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_utils/not_found_error_page'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_utils_not_found_error_page', 
		];
	}
}
