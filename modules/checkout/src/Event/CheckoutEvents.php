<?php
namespace Drupal\gv_fanatics_plus_checkout\Event;
/**
 * Declara eventos correspondientes al proceso de Checkout:
 * - sumisión del formulario del paso de selección de productos
 * - sumisión del formulario de selección de método de pago
 * 
 */
final class CheckoutEvents {
  const SELECT_PRODUCTS_FORM_SUBMIT = 'gv_fanatics_plus_checkout.select_products_form_submit';
  const SELECT_PAYMENT_METHOD_FORM_SUBMIT = 'gv_fanatics_plus_checkout.payment_method_form_submit';
}

?>