<?php

namespace Drupal\gv_fanatics_plus_checkout;

use Drupal\Core\Url;

/**
 * Modelo representativo del servicio de TPV de Doblemente
 */
class TPV {
	private $apiClient;
	
	const KO_ROUTE = 'gv_fanatics_plus_order.order_payment_failure';
	const OK_PAST_ORDER_ROUTE = 'gv_fanatics_plus_order.order_detail';
	const OK_POST_PAYMENT_ROUTE = 'gv_fanatics_plus_checkout.post_payment';
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function pay($IDSession, $IDBooking, $IDPayment, $UrlOK, $UrlKO) {
		return $this->apiClient->tpv()->pay($IDSession, $IDBooking, $IDPayment, $UrlOK, $UrlKO);
	}
	
	public function decryptGetInfoPostTPV($IDSessionEncrypted, $IDBookingEncrypted) {
		return $this->apiClient->tpv()->descryptGetInfoPostTPV($IDSessionEncrypted, $IDBookingEncrypted);
	}
	
	public function confirmBooking($IDSession, $IDPaymentMethod, $RelatedBookingLocator = NULL, $ClientReference = NULL, $InternalNotes = NULL, $IDBookingOffice = NULL) {
		return $this->apiClient->booking()->confirm($IDSession, $IDPaymentMethod, $ClientReference, $IDBookingOffice, $RelatedBookingLocator, $InternalNotes);
	}
	
	public function getKOUrl($pendingPaymentID) {
		return Url::fromRoute(TPV::KO_ROUTE, ['pendingPaymentID' => $pendingPaymentID], ['absolute' => TRUE])->toString();
	}
	
	public function getOKUrl($orderID, $pastOrder = FALSE) {
		if (!$pastOrder) {
			return Url::fromRoute(TPV::OK_POST_PAYMENT_ROUTE, ['orderID' => $orderID], ['absolute' => TRUE])->toString();
		}
		
		return Url::fromRoute(TPV::OK_PAST_ORDER_ROUTE, ['orderID' => $orderID], ['absolute' => TRUE, 'query' => ['post_payment' => 1]])->toString();
	}
}

?>
