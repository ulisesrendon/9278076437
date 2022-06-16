<?php

namespace Drupal\gv_fplus_auth\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Clase con la verificación de usuario activo y autenticado.
 *
 * @package Drupal\gv_fplus_auth\Access
 */
class LoggedInAccessCheck implements AccessInterface {

	private $session;

	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
	}

	public function access() {
		$isActiveAndLogged = $this->session->isActiveAndLogged();
		if (!$isActiveAndLogged) {
			return AccessResult::forbidden();
		}
		
		return AccessResult::allowed();
	}

}
