<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para registrar eventos de TagManager para registrar opciones del usuario en la selección de seguros para sus servicios.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class TagManagerInsuranceCheckoutOptionAjaxCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $option) {
  	$this->selector = $selector;
    $this->option = $option;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusTagManagerInsuranceCheckoutOptionAjaxCommand',
      'method' => NULL,
      'selector' => $this->selector,
      'option' => $this->option
    );
  }
}

?>
