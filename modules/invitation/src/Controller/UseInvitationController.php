<?php

namespace Drupal\gv_fanatics_plus_invitation\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controlador para usar una invitación
 */
class UseInvitationController extends ControllerBase {

	private $invitation;
	private $session;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$invitation = $container->get('gv_fanatics_plus_invitation.invitation');
		
		return new static($session, $invitation);
	}
	
	public function __construct($session, $invitation) {
		$this->session = $session;
		$this->invitation = $invitation;
	}

	public function redeem(RouteMatchInterface $route_match) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$orderID = $route_match->getParameter('orderID');
		$serviceID = $route_match->getParameter('serviceID');
		
		$redemptionResult = $this->invitation->redeem($this->session->getIdentifier(), $orderID, $serviceID);

		$createdLocator = $redemptionResult->LocatorCreated;
		$IDBookingCreated = $redemptionResult->IDBookingCreated;
		
		$targetURL = Url::fromRoute('gv_fanatics_plus_invitation.invitation_list');
		
		$messenger = \Drupal::messenger();
		$messenger->addMessage($translationService->translate('INVITATIONS.INVITATION_USED_NOTICE_V2', ['@locator' => $createdLocator]), $messenger::TYPE_STATUS , TRUE);
		
		$response = new RedirectResponse($targetURL->toString(TRUE)->getGeneratedUrl(), 307);
		return $response;
	}

}

?>
