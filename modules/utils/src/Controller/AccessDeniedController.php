<?php

namespace Drupal\gv_fanatics_plus_utils\Controller;

/**
 * Controlador de página de acceso denegado
 */
class AccessDeniedController {
	
	public function show() {
		
		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_utils/access_denied_error_page'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_utils_access_denied_error_page', 
		];
	}
}
