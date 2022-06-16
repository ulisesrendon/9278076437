<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

/**
 * Entidad responsable por retornar el paso correspondiente del proceso de post-pago para un determinado expediente
 */
class PostPaymentResolver implements PostPaymentResolverInterface, EventSubscriberInterface {
	
	const SESSION_STORAGE_PREFIX = 'gv_fanatics_plus_checkout.post_payment_resolver';
	
	protected $apiClient;
	protected $sessionManager;
	protected $defaultStep;
	
	protected $cart;
	protected $formBuilder;
	protected $baseCheckoutRoute;
	
	/**
    * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        $events = [
        	//CheckoutEvents::SELECT_PRODUCTS_FORM_SUBMIT => 'onCheckoutFormSubmit'
        ];
		
        return $events;
    }
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$this->sessionManager = \Drupal::service('user.private_tempstore')->get(static::SESSION_STORAGE_PREFIX);
		$this->formBuilder = \Drupal::service('form_builder');
		
		$this->defaultStep = 1;
		$this->baseCheckoutRoute = 'gv_fanatics_plus_checkout.form';
	}
	
	public function resolveFromCart($stepID) {
		$currentCart = $this->cart->getCurrentDetail(TRUE);
		return $this->resolve($currentCart->Booking->Identifier, $stepID);
	}
	
	public function resolve($currentOrderID, $stepID) {
		if (!isset($currentOrderID) || !is_numeric($currentOrderID)) {
			return NULL;
		}
						
		$previousOrderID = $this->sessionManager->get('order_id', NULL);
		if ($previousOrderID != $currentOrderID) { // New order, set default state
			$this->sessionManager->set('order_id', $currentOrderID);
			$this->sessionManager->set('current_step_id', $this->defaultStep);
			
			return $this->_resolveForSimplePayment($currentOrderID, $this->defaultStep);
		}
		
		return $this->_resolveForSimplePayment($currentOrderID, $stepID);
	}
	
	protected function _resolveForSimplePayment($currentOrderID, $stepID) {
		$totalSteps = 2;
		
		if (!isset($stepID) || $stepID <= 1) {
			$destinationUrl = Url::fromRoute($this->baseCheckoutRoute, ['step' => CheckoutOrderSteps::POST_PAYMENT], ['query' => ['step' => 2]]);
			return $this->formBuilder->getForm(ShippingMethodSelectForm::class, $currentOrderID, $stepID, $totalSteps, $destinationUrl);
		}
		
		$destinationUrl = Url::fromRoute($this->baseCheckoutRoute, ['step' => CheckoutOrderSteps::COMPLETE]);
		return $this->formBuilder->getForm(ShippingDataForm::class, $currentOrderID, $stepID, $totalSteps, $destinationUrl);
	}
	
	public function getDefault() {
		$totalSteps = 2;
		$destinationUrl = Url::fromRoute($this->baseCheckoutRoute, ['step' => CheckoutOrderSteps::POST_PAYMENT], ['query' => ['step' => 2]]);
		return $this->formBuilder->getForm(ShippingMethodSelectForm::class, $currentOrderID, $stepID, $totalSteps, $destinationUrl);
	}
}

?>