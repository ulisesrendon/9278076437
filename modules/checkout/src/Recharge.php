<?php

namespace Drupal\gv_fanatics_plus_checkout;

use Drupal\Core\Url;

/**
 * Modelos representativos de la información de recarga de expedientes de Doblemente
 */
 
class BookingRechargeable {
	public $Identifier;
	public $BookingLocator;
	public $Rechargeable;
	
	/** @var BookingServiceRechargeable[] */
	public $Services;
}

class BookingServiceRechargeable {
	public $Identifier;
	public $Rechargeable;
	public $Recharged;
	public $Printed;
	public $RechargeRequest;
	public $WTPNumber;
}

class Recharge {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function bookingRechargeable($IDSession, $IDBooking) {
		$booking = $this->apiClient->recharge()->bookingRechargeable($IDSession, $IDBooking)->Booking;
		$mapper = new \JsonMapper();
		$mapper->bStrictNullTypes = false;
		
		$mappedBooking = $mapper->map($booking, new BookingRechargeable());
		return $mappedBooking;
	}
	
	public function bookingRecharge($IDSession, $IDBooking, $IDBookingService, $WTPNumberPartial) {
		return $this->apiClient->recharge()->bookingRecharge($IDSession, $IDBooking, $IDBookingService, $WTPNumberPartial);
	}
	
	public function bookingSetRechargeRequest($IDSession, $IDBooking, $IDBookingService, $RechargeRequest) {
		return $this->apiClient->recharge()->bookingSetRechargeRequest($IDSession, $IDBooking, $IDBookingService, $RechargeRequest);
	}
}

?>
