<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\CloseOffCanvasDialogCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerCheckoutOptionAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Controlador genérico para cerrar modales.
 * IMPORTANTE: No usar este controlador fuera del contexto del formulario de selección de productos.
 */
class CloseModalController extends ControllerBase {
	
  public function __construct() {}

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function close() {    
    $response = new AjaxResponse();	
	
	$response->addCommand(new CloseDialogCommand());
	$response->addCommand(new TagManagerCheckoutOptionAjaxCommand(NULL, 2));
	$response->addCommand(new InvokeCommand('#gv-fanatics-plus-checkout-select-product', 'submit', []));
    
    return $response;
  }

}
