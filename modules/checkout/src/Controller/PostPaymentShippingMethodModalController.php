<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Drupal\gv_fanatics_plus_checkout\Ajax\AddOffCanvasDialogBackgroundCommand;

use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador de modal del paso de definición de métodos de envío / recarga del proceso de post-pago
 */
class PostPaymentShippingMethodModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $formBuilder;

  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Abre la modal del paso de definición de métodos de envío / recarga del proceso de post-pago
   */
  public function openModalForm() {
  	/*$productID = $route_match->getParameter('productid');
	$insuranceID = $route_match->getParameter('insuranceid');*/
    
    $response = new AjaxResponse();
	
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\PostPaymentShippingMethodModalForm');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Options', [], ['context' => TranslationContext::CHECKOUT]), $modal_form, ['width' => '800']));
    $response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
    
    return $response;
  }
}
