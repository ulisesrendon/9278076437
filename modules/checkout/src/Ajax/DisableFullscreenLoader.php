<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para desactivar el preloader de pantalla completa
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class DisableFullscreenLoader implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector) {
    $this->selector = $selector;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusDisableFullscreenLoader',
      'method' => NULL,
      'selector' => $this->selector
    );
  }
}

?>
