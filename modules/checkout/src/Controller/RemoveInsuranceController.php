<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
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
 * Controlador para la acción de remover un seguro de un servicio del carrito
 */
class RemoveInsuranceController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $cart;

  public function __construct($cart) {
    $this->cart = $cart;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fanatics_plus_cart.cart')
    );
  }

  public function removeInsurance(RouteMatchInterface $route_match) {
  	$productID = $route_match->getParameter('productid');
    
    $response = new AjaxResponse();
		
	$cartContents = $this->cart->getCurrentDetail()->Booking;
	foreach ($cartContents->Services as $index => $service) {
		if ($service->Identifier != $productID) {
			continue;
		}
		
		if (($service->AvailableInsurances == NULL || count($service->AvailableInsurances) <= 0) and $service->SeasonPassData->Insurance != NULL) {
			$insurance = new \stdClass();
			$insurance->IDInsurance = $service->SeasonPassData->IDInsurance;
			$insurance->Amount = $service->SeasonPassData->InsuranceAmount;
			$response->addCommand(new TagManagerRemoveInsuranceAjaxCommand(NULL, $insurance));
		}
	}
	
	$result = $this->cart->removeSeasonPassInsurance($productID);
	
	$response->addCommand(new RefreshCartAjaxCommand(NULL));
	
    return $response;
  }

}
