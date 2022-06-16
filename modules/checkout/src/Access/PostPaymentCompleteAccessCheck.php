<?php

namespace Drupal\gv_fanatics_plus_checkout\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Entidad que determina el a la ruta final del proceso de Post-pago (proceso completado).
 *
 * @package Drupal\gv_fanatics_plus_checkout\Access
 */
class PostPaymentCompleteAccessCheck implements AccessInterface {

	private $session;
	private $order;

	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
		$this->order = \Drupal::service('gv_fanatics_plus_order.order');
	}

	public function access() {
		$orderID = \Drupal::routeMatch()->getParameter('orderID');
		
		// Check if logged user owns order
		if (!is_numeric($orderID)) { // Invalid Order ID format
			return AccessResult::forbidden();
		}
		
		$order = $this->order->getFromID($orderID, TRUE, TRUE);
		if (!isset($order)) { // No order exists
			return AccessResult::forbidden();
		}
		
		if ($order->hasPendingShippingMethod()) {
			return AccessResult::forbidden();
		}
		
		return AccessResult::allowed();
	}

}
