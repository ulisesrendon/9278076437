<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para cerrar los diálogos emergentes laterales
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class CloseOffCanvasDialogCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector) {
    $this->selector = $selector;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusCloseOffCanvasDialog',
      'method' => NULL,
      'selector' => $this->selector
    );
  }
}

?>
