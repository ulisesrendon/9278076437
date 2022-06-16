<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para registrar eventos de TagManager correspondientes a las acciones añadir un producto al carrito.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class TagManagerAddProductAjaxCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $bookingService) {
  	$this->selector = $selector;
    $this->bookingService = $bookingService;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusTagManagerAddProductAjaxCommand',
      'method' => NULL,
      'selector' => $this->selector,
      'booking_service' => $this->bookingService
    );
  }
}

?>
