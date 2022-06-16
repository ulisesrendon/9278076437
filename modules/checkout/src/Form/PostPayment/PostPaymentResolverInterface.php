<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

/**
 * Interfaz para la entidad que retorna el paso de post-pago del expediente
 */
interface PostPaymentResolverInterface {
	
	public function resolveFromCart($stepID);
	public function resolve($currentOrderID, $stepID);
	public function getDefault();
	
}

?>