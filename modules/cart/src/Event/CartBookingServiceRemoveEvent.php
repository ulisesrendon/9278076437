<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart entity add event.
 *
 * @see \Drupal\gv_fanatics_plus_cart\Event\CartEvents
 */
class CartBookingServiceRemoveEvent extends Event {

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
