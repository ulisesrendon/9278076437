<?php

namespace Drupal\gv_fanatics_plus_cart\Event;

/**
 * Representa todos los eventos disponibles en el contexto de la gestión del carrito
 */
final class CartEvents {
	const CART_BOOKING_SERVICE_ADD = 'gv_fanatics_plus_cart.booking_service.add';
	const CART_BOOKING_SERVICE_REMOVE = 'gv_fanatics_plus_cart.booking_service.remove';
	const CART_BOOKING_SERVICE_INSURANCE_ADD = 'gv_fanatics_plus_cart.booking_service_insurance.add';
	const CART_BOOKING_SERVICE_INSURANCE_REMOVE = 'gv_fanatics_plus_cart.booking_service_insurance.remove';
	const CART_BOOKING_SERVICE_CONFIRM = 'gv_fanatics_plus_cart.booking_service.confirm';
}

?>
