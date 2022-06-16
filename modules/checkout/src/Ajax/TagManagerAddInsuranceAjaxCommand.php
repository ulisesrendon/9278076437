<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para registrar eventos de TagManager correspondientes a las acciones de añadir un seguro a un servicio del carrito.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class TagManagerAddInsuranceAjaxCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $insurance) {
  	$this->selector = $selector;
    $this->insurance = $insurance;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusTagManagerAddInsuranceAjaxCommand',
      'method' => NULL,
      'selector' => $this->selector,
      'insurance' => $this->insurance
    );
  }
}

?>
