<?php

namespace Drupal\gv_fanatics_plus_checkout\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Define un evento de selección de método de pago
 */
class PaymentMethodSelectedEvent extends Event {

	protected $IDSession;
	protected $IDBooking;
	protected $IDPayment;

	public function __construct($IDSession, $IDBooking, $IDPayment) {
		$this->IDSession = $IDSession;
		$this->IDBooking = $IDBooking;
		$this->IDPayment = $IDPayment;
	}

	public function getIDSession() {
		return $this->IDSession;
	}

	public function getIDBooking() {
		return $this->IDBooking;
	}

	public function getIDPayment() {
		return $this->IDPayment;
	}

}
