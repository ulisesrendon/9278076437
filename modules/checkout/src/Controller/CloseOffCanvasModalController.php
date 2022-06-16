<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\CloseOffCanvasDialogCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerInsuranceCheckoutOptionAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Controlador para cerrar modales emergentes laterales.
 */
class CloseOffCanvasModalController extends ControllerBase {
	
  public function __construct() {}

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function close() {
  	$request = \Drupal::request();
	$insuranceModal = $request->query->get('insurance_modal');
	
	$response = new AjaxResponse();	
	
	if (isset($insuranceModal) && $insuranceModal == 1) {
		$response->addCommand(new TagManagerInsuranceCheckoutOptionAjaxCommand(NULL, 'Sin Seguro'));
	}
    
	$response->addCommand(new CloseOffCanvasDialogCommand(NULL));
    return $response;
  }

}
