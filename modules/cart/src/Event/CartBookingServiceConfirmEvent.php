<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart entity add event.
 *
 * @see \Drupal\gv_fanatics_plus_cart\Event\CartEvents
 */
class CartBookingServiceConfirmEvent extends Event {

	protected $cart;

	protected $bookingResult;

	public function __construct(CartInterface $cart, $bookingResult) {
		$this -> cart = $cart;
		$this->bookingResult = $bookingResult;
	}

	public function getCart() {
		return $this->cart;
	}

	public function getBookingResult() {
		return $this->bookingResult;
	}

}
