<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para refrescar el bloque del carrito de checkout.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class RefreshCartAjaxCommand implements CommandInterface {
  
  protected $enable;
  
  // Constructs an SlideDownCommand object.
  public function __construct($selector) {
    $this->selector = $selector;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusRefreshCart',
      'method' => NULL,
      'selector' => $this->selector
    );
  }
}

?>
