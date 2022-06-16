<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para registrar eventos de TagManager correspondientes a la acción de remover un servicio del carrito.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class TagManagerRemoveProductAjaxCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $bookingService) {
  	$this->selector = $selector;
    $this->bookingService = $bookingService;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusTagManagerRemoveProductAjaxCommand',
      'method' => NULL,
      'selector' => $this->selector,
      'booking_service' => $this->bookingService
    );
  }
}

?>
