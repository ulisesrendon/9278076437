<?php

namespace Drupal\gv_fanatics_plus_checkout;

use Drupal\Core\Url;

/**
 * Modelo representativo del servicio de firma electrónica de Logalty de Doblemente
 */
class Logalty {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function checkSignatureStatus($IDBooking) {
		return $this->apiClient->logalty()->checkSignatureStatus($IDBooking);
	}
	
	public function signAndGetURL($IDBooking, $IDSession) {
		return $this->apiClient->logalty()->signAndGetURL($IDBooking, $IDSession)->URLSignLogalty;
	}
}

?>
