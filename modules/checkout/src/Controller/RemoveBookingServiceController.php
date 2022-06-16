<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\EnableDisableProductFormSubmitAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\SetProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveInsuranceAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Controlador para la acción de remover un servicio del carrito
 */
class RemoveBookingServiceController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $cart;
  protected $session;

  public function __construct($session, $cart) {
  	$this->session = $session;
    $this->cart = $cart;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fplus.session'),
      $container->get('gv_fanatics_plus_cart.cart')
    );
  }

  public function removeBookingService(RouteMatchInterface $route_match) {
  	$productID = $route_match->getParameter('productid');
    
    $response = new AjaxResponse();
	
	$cartContents = $this->cart->getCurrentDetail()->Booking;
	foreach ($cartContents->Services as $index => $service) {
		if ($service->Identifier == $productID) {
			$response->addCommand(new TagManagerRemoveProductAjaxCommand(NULL, $service));
			if (($service->AvailableInsurances == NULL || count($service->AvailableInsurances) <= 0) and $service->SeasonPassData->Insurance != NULL) {
				$insurance = new \stdClass();
				$insurance->IDInsurance = $service->SeasonPassData->IDInsurance;
				$insurance->Amount = $service->SeasonPassData->InsuranceAmount;
				$response->addCommand(new TagManagerRemoveInsuranceAjaxCommand(NULL, $insurance));
			}
		}
	}
	
	try {
		$result = $this->cart->removeBookingService($productID);
	} catch (\GuzzleHttp\Exception\ClientException $e) {}
	
	$updatedCartContents = $this->cart->getCurrentDetail()->Booking;
	if (count($updatedCartContents->Services) <= 0) {
		$response->addCommand(new EnableDisableProductFormSubmitAjaxCommand(NULL, FALSE));
	}
	
	$response->addCommand(new SetProductAjaxCommand(NULL));	
	$response->addCommand(new RefreshCartAjaxCommand(NULL));
	
    return $response;
  }

}
