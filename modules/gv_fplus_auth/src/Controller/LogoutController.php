<?php

namespace Drupal\gv_fplus_auth\Controller;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Controller\ControllerBase;

/**
 * Controlador de cierre de sesión
 */
class LogoutController extends ControllerBase {

	private $session;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		return new static($session);
	}
	
	public function __construct($session) {
		$this->session = $session;
	}

	public function logout() {
		$response = $this->session->logout();
		return $this->redirect('gv_fplus_auth.email_check_form');
	}
}

?>
