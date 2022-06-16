<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controlador que procesa un error de pago y muestra la página de error correspondiente
 */
class OrderPaymentFailureController extends ControllerBase {

	private $order;
	private $session;
	private $TPV;
	private $contactPageLinkBuilder;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$order = $container->get('gv_fanatics_plus_order.order');
		$TPV = $container->get('gv_fanatics_plus_checkout.tpv');
		$contactPageLinkBuilder = $container->get('gv_fanatics_plus_contact.contact_page_link_builder');
		
		return new static($apiClient, $session, $order, $TPV, $contactPageLinkBuilder);
	}
	
	public function __construct($apiClient, $session, $order, $TPV, $contactPageLinkBuilder) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->order = $order;
		$this->TPV = $TPV;
		$this->contactPageLinkBuilder = $contactPageLinkBuilder;
	}

	public function onPaymentError(RouteMatchInterface $route_match) {
		$pendingPaymentID = $route_match->getParameter('pendingPaymentID');
		
		$request = \Drupal::request();
		$dbmsessionid = $request->query->get('dbmsessionid');
		$dbmbookingid = $request->query->get('dbmbookingid');
		
		$decryptedData = $this->TPV->decryptGetInfoPostTPV($dbmsessionid, $dbmbookingid);
		
		if (!isset($decryptedData) || !isset($decryptedData->Booking)) {
			throw new NotFoundHttpException();
		}
		
		$booking = $decryptedData->Booking;
		$orderID = $booking->Identifier;
		if (!is_numeric($orderID) || !is_numeric($pendingPaymentID)) {
			throw new NotFoundHttpException();
		}
		
		$IDSession = $this->session->getIdentifier();
				
		$currentUserID = $this->session->getIDUser();
		if ($booking->IDUser != $currentUserID) { // Not the owner
			throw new AccessDeniedHttpException();
		}
		
		$targetPayment = array_pop(array_reverse(array_filter($booking->Payments, function ($payment) use ($pendingPaymentID) { return $payment->Identifier == $pendingPaymentID; })));		
		if (!isset($targetPayment)) {
			throw new NotFoundHttpException();
		}
		
		if ($targetPayment->Consolidated) {
			throw new NotFoundHttpException();
		}
		
		$urlOK = $this->TPV->getOKUrl($orderID, TRUE);
		$urlKO = $this->TPV->getKOUrl($pendingPaymentID);
		
		$paymentResult = $this->TPV->pay($IDSession, $orderID, $pendingPaymentID, $urlOK, $urlKO);
		$URLTPV = $paymentResult->URLTPV;
		$contactUrl = $this->contactPageLinkBuilder->buildURL($booking->BookingLocator, TRUE);

		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_order/order_payment_failure'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_order_payment_failure', 
			'#URLTPV' => $URLTPV, 
			'#order' => $booking, 
			'#contactURL' => $contactUrl
		];
	}

}

?>
