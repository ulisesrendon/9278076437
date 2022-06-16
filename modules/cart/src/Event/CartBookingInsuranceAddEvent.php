<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Define un evento para cuando se añade un seguro a un servicio
 *
 * @see \Drupal\gv_fanatics_plus_cart\Event\CartEvents
 */
class CartBookingInsuranceAddEvent extends Event {

	protected $cart;

	protected $bookingService;

	protected $insuranceID;

	public function __construct(CartInterface $cart, $bookingService, $insuranceID) {
		$this -> cart = $cart;
		$this->bookingService = $bookingService;
		$this->insuranceID = $insuranceID;
	}

	public function getCart() {
		return $this->cart;
	}

	public function getBookingService() {
		return $this->bookingService;
	}

	public function getInsuranceID() {
		return $this -> insuranceID;
	}

}
