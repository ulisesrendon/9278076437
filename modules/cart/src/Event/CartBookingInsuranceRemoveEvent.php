<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Define un evento para cuando se remueve un seguro de un servicio
 *
 * @see \Drupal\gv_fanatics_plus_cart\Event\CartEvents
 */
class CartBookingInsuranceRemoveEvent extends Event {

	protected $cart;

	protected $bookingService;

	public function __construct(CartInterface $cart, $bookingService) {
		$this -> cart = $cart;
		$this->bookingService = $bookingService;
	}

	public function getCart() {
		return $this->cart;
	}

	public function getBookingService() {
		return $this->bookingService;
	}
}
