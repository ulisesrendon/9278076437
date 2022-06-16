<?php

namespace Drupal\gv_fanatics_plus_checkout;

/**
 * Modelo representativo de métodos de pago de Doblemente
 */
class BookingPaymentMethod {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function getBySessionID($sessionID) {
		return $this->apiClient->bookingHelper()->getPaymentMethods($sessionID);
	}
}

?>
