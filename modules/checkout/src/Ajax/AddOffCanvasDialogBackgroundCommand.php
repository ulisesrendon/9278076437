<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para añadir un fondo a los diálogos emergentes laterales
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class AddOffCanvasDialogBackgroundCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector) {
    $this->selector = $selector;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusAddOffCanvasDialogBackground',
      'method' => NULL,
      'selector' => $this->selector
    );
  }
}

?>
