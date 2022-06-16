<?php

namespace Drupal\gv_fanatics_plus_checkout;

/**
 * Define constantes para las opciones de selección en el paso de selección de método de envío / datos de envío del proceso de post-pago
 */
final class BookingOfficeOptions {
	const HOME_DELIVERY = 7;
	const BOX_OFFICE_PICKUP = 6;
	const RECHARGE_FORFAIT = 5;
	const FORFAIT_RECHARGED = 100;
	const PRINTED = 101;
	const DEFAULT_BOX_OFFICE = 4;
}

?>
