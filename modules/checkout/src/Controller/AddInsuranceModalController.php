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

/**
 * Controlador de la modal de seguro complementario al servicio
 */
class AddInsuranceModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $formBuilder;
  protected $cart;
  protected $translationService;
  
  public function __construct($cart, FormBuilder $formBuilder, $translationService) {
    $this->cart = $cart;
    $this->formBuilder = $formBuilder;
	$this->translationService = $translationService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fanatics_plus_cart.cart'),
      $container->get('form_builder'),
      $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  /**
   * Abre el formulario de modal
   */
  public function openModalForm(RouteMatchInterface $route_match) {
  	$productID = $route_match->getParameter('productid');
	$insuranceID = $route_match->getParameter('insuranceid');
    
    $response = new AjaxResponse();
	
	$insurances = $this->cart->getSeasonPassInsurances($productID);
	if (count($insurances) <= 0) { // No complementary services, don't open modal
		return $response;
	}
	
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\AddInsuranceModalForm', $productID, $insuranceID);

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenOffCanvasDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_PRODUCT_HEADER'), $modal_form, ['width' => '800']));
    $response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
    
    return $response;
  }
}
