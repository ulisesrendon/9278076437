<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Controlador que procesa los pagos de los cobros pendientes de un expediente
 */
class OrderPaymentController extends ControllerBase {

	private $order;
	private $session;
	private $TPV;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$order = $container->get('gv_fanatics_plus_order.order');
		$TPV = $container->get('gv_fanatics_plus_checkout.tpv');
		
		return new static($apiClient, $session, $order, $TPV);
	}
	
	public function __construct($apiClient, $session, $order, $TPV) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->order = $order;
		$this->TPV = $TPV;
	}

	public function resolvePendingPayment(RouteMatchInterface $route_match) {
		$orderID = $route_match->getParameter('orderID');
		$pendingPaymentID = $route_match->getParameter('pendingPaymentID');

		// 1- Check if order ID is numeric
		// 2 - Check if payment ID is numeric
		// 2 - Load order, check if valid
		// 3 - Check if pending payment ID is valid and is not payed
		// 3 - Check if current user is the owner of the order
		
		// return 404 if invalid
		
		if (!is_numeric($orderID) || !is_numeric($pendingPaymentID)) {
			throw new NotFoundHttpException();
		}
		
		$IDSession = $this->session->getIdentifier();
		$booking = $this->order->getFromID($orderID, FALSE, TRUE);
		if (!isset($booking)) {
			throw new NotFoundHttpException();
		}
				
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
		
		$response = new TrustedRedirectResponse($URLTPV, 307);
		$response->send();
		
		return [];
	}

}

?>
