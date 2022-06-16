<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para activar / desactivar el botón de sumisión del formulario de selección de productos del checkout.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class EnableDisableProductFormSubmitAjaxCommand implements CommandInterface {
  
  protected $enable;
  
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $enable = FALSE) {
    $this->selector = $selector;
    $this->enable = $enable;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusEnableDisableProductFormSubmit',
      'method' => NULL,
      'selector' => $this->selector,
      'enable' => $this->enable,
    );
  }
}

?>
