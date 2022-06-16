<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\CloseOffCanvasDialogCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\StartIntroJsAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Controlador del de recordatorio al usuario de que tiene integrantes sin producto o bien no tiene integrantes registrados.
 */
class AddIntegrantProductsReminderModalController extends ControllerBase {
	
  public function __construct() {}

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function addReminder() {
    $response = new AjaxResponse();
	
	$response->addCommand(new StartIntroJsAjaxCommand(NULL));
	$response->addCommand(new CloseDialogCommand());
	
    return $response;
  }

}
