<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Define un evento para cuando se añade un servicio al carrito
 *
 * @see \Drupal\gv_fanatics_plus_cart\Event\CartEvents
 */
class CartBookingServiceAddEvent extends Event {

	protected $cart;

	protected $bookingService;

	protected $quantity;

	public function __construct(CartInterface $cart, $bookingService, $quantity) {
		$this -> cart = $cart;
		$this->bookingService = $bookingService;
		$this->quantity = $quantity;
	}

	public function getCart() {
		return $this->cart;
	}

	public function getBookingService() {
		return $this->bookingService;
	}

	public function getQuantity() {
		return $this -> quantity;
	}

}
