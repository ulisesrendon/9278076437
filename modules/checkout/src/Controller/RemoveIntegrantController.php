<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Controlador para acción de eliminar un integrante
 */
class RemoveIntegrantController extends ControllerBase {

	private $session;
	private $integrant;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$integrant = $container->get('gv_fanatics_plus_checkout.integrant');
		
		return new static($session, $integrant);
	}
	
	public function __construct($session, $integrant) {
		$this->session = $session;
		$this->integrant = $integrant;
	}

	public function removeIntegrant(RouteMatchInterface $route_match) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		$integrantID = $route_match->getParameter('integrantID');
		
		if (!is_numeric($integrantID) || !is_numeric($integrantID)) {
			throw new NotFoundHttpException();
		}
		
		$validIntegrant = FALSE;
		$members = $this->integrant->listMembers($this->session->getIDClient())->List;
		foreach ($members as $member) {
			if ($member->IntegrantID == $integrantID) {
				$validIntegrant = TRUE;
				break;
			}
		}
		
		if (!$validIntegrant) {
			throw new NotFoundHttpException();
		}
		
		$this->integrant->delete($integrantID);
		
		$targetURL = Url::fromRoute('gv_fanatics_plus_checkout.integrant_list');
		$response = new RedirectResponse($targetURL->toString(), 307);
		$response->send();
		
		\Drupal::messenger()->addMessage($translationService->translate('MY_INTEGRANTS.INTEGRANT_REMOVED'));
		
		return [];
	}

}

?>
