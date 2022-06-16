<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\SetProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\EnableDisableProductFormSubmitAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\AddOffCanvasDialogBackgroundCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerAddProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveProductAjaxCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador de la modal de confirmación de modificación de producto para un usuario / integrante.
 */
class ChangeProductModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $session;
  protected $cart;
  protected $translationService;
  
  protected $formBuilder;

  public function __construct($session, $cart, FormBuilder $formBuilder, $translationService) {
  	$this->session = $session;
	$this->cart = $cart;
    $this->formBuilder = $formBuilder;
	$this->translationService = $translationService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fplus.session'),
      $container->get('gv_fanatics_plus_cart.cart'),
      $container->get('form_builder'),
      $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  /**
   * Abre el formulario de la modal
   */
  public function openModalForm(RouteMatchInterface $route_match) {
  	$searchID = $route_match->getParameter('searchid');
  	$productCode = $route_match->getParameter('productcode');
	$productId = $route_match->getParameter('productId');
    $response = new AjaxResponse();

	$cartContents = $this->cart->getCurrentDetail()->Booking;
	$isIntegrantActive = $this->session->isManagingIntegrant();
	$ownerClientID = $cartContents->IDClient;
	$bookingServiceToRemove = NULL;
	foreach ($cartContents->Services as $index => $service) {
		if ($isIntegrantActive && $service->SeasonPassData->IDClient != $this->session->getActiveIntegrantClientID()) {
			continue;
		}
		
		if (!$isIntegrantActive && $service->SeasonPassData->IDClient != $ownerClientID) {
			continue;
		}
		
		$bookingServiceToRemove = $service;
	}
	
	if (isset($bookingServiceToRemove)) {
		$previousProductId = $bookingServiceToRemove->SeasonPassData->IDProduct;
		if ($previousProductId == $productId) {
			return $response;
		}		
	}

	if (isset($bookingServiceToRemove)) {
	    // Get the modal form using the form builder.
    	$changeProductModalForm = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\ChangeProductModalForm', $searchID, $productCode);
    	// Add an AJAX command to open a modal dialog with the form as the content.
    	$response->addCommand(new OpenModalDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.CHANGE_PRODUCT_MODAL_HEADER'), $changeProductModalForm, ['width' => '800']));	
	} else {
		$result = $this->cart->addBookingService($searchID, $productCode);
		$response->addCommand(new SetProductAjaxCommand(NULL, $productCode));
		
		$updatedCartContents = $this->cart->getCurrentDetail()->Booking;
		
		if (count($updatedCartContents->Services) > 0) {
			$response->addCommand(new EnableDisableProductFormSubmitAjaxCommand(NULL, TRUE));
		}
		
		$bookingServiceToTrack = NULL;
		foreach ($updatedCartContents->Services as $index => $service) {
			if ($isIntegrantActive && $service->SeasonPassData->IDClient != $this->session->getActiveIntegrantClientID()) {
				continue;
			}
			
			if (!$isIntegrantActive && $service->SeasonPassData->IDClient != $ownerClientID) {
				continue;
			}
			
			$bookingServiceToTrack = $service;
		}
		

		$response->addCommand(new TagManagerAddProductAjaxCommand(NULL, $bookingServiceToTrack));
		
		// get serviceID
		$productID = $result->Identifier;
		
		$insurances = $this->cart->getSeasonPassInsurances($productID);
		if (count($insurances) <= 0) {
			return $response;
		}
		
		// Get the modal form using the form builder.
    	$addInsuranceModalForm = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\AddInsuranceModalForm', $productID, NULL);

    	// Add an AJAX command to open a modal dialog with the form as the content.
    	$response->addCommand(new OpenOffCanvasDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_PRODUCT_HEADER'), $addInsuranceModalForm, ['width' => '800']));
		$response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
	}

    return $response;
  }

}
