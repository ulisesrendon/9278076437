<?php

namespace Drupal\gv_fanatics_plus_checkout\Ajax;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Comando para registrar eventos de TagManager para registrar opciones del usuario en el proceso de checkout.
 * 
 * @see js/gv_fanatics_plus_checkout.change_product_ajax_commands.js
 */
class TagManagerCheckoutOptionAjaxCommand implements CommandInterface {
    
  // Constructs an SlideDownCommand object.
  public function __construct($selector, $step, $option = NULL) {
  	$this->selector = $selector;
	$this->step = $step;
    $this->option = $option;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'gvFanaticsPlusTagManagerCheckoutOptionAjaxCommand',
      'method' => NULL,
      'selector' => $this->selector,
      'step' => $this->step,
      'option' => $this->option
    );
  }
}

?>
