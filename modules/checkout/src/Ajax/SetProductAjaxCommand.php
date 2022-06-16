<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para seleccionar un producto en el formulario de selección de producto de checkout.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class SetProductAjaxCommand implements CommandInterface {
  
  protected $productCode;
  
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $productCode = NULL) {
    $this->selector = $selector;
    $this->productCode = $productCode;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusSetProduct',
      'method' => NULL,
      'selector' => $this->selector,
      'productCode' => $this->productCode,
    );
  }
}

?>
