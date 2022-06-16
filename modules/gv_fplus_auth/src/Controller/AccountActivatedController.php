<?php

namespace Drupal\gv_fplus_auth\Controller;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use \Drupal\Core\Controller\ControllerBase;

/**
 * Controlador de ruta de cuenta activada.
 */
class AccountActivatedController extends ControllerBase {

	private $session;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		return new static($session);
	}
	
	public function __construct($session) {
		$this->session = $session;
	}

	public function accountActivatedAction() {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$currentRequest = \Drupal::request();
		$email = $currentRequest->query->get('email');
		$id_language = $currentRequest->query->get('id_language');
		
		$valid_email = \Drupal::service('email.validator')->isValid($email);
		if (!$valid_email) {
			\Drupal::messenger()->addMessage($translationService->translate('ACTIVATED_ACCOUNT.INVALID_EMAIL'), 'error');
			return $this->redirect('gv_fplus_auth.email_check_form');
		}
		
		$this->session->logout();
		
		\Drupal::messenger()->addMessage($translationService->translate('ACTIVATED_ACCOUNT.SUCCESS_MESSAGE'));
		return $this->redirect('gv_fplus_auth.login_form', ['email' => $email]);
	}

}

?>
