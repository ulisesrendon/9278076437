<?php

namespace Drupal\gv_fanatics_plus_checkout;

use Drupal\gv_fplus_auth\Event\AuthEvents;
use Drupal\gv_fanatics_plus_checkout\Event\CheckoutEvents;
use Drupal\gv_fanatics_plus_checkout\Event\PaymentMethodSelectedEvent;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

final class PostPaymentOrderSteps {
	const LOGALTY = 'gv_fanatics_plus_checkout.post_payment_logalty';
	const SHIPPING_METHOD_SELECT = 'gv_fanatics_plus_checkout.post_payment_shipping_method';
	const DOCUMENTS = 'gv_fanatics_plus_checkout.post_payment_documents';
	const SHIPPING_DATA = 'gv_fanatics_plus_checkout.post_payment_shipping_data';
	const COMPLETE = 'gv_fanatics_plus_checkout.post_payment_shipping_data_complete';
}

/**
 * Entidad que gestiona el estado del expediente para el proceso de post-pago
 */
class PostPaymentOrderManager implements EventSubscriberInterface {
	
	const ENCRYPTION_KEY = 'mfZEbljN90^$e4OE';
	const ENCRYPTION_METHOD = 'aes-128-ctr';
	const SESSION_STORAGE_PREFIX = 'gv_fanatics_plus_checkout.post_payment_manager';
	
	private $session;
	private $cart;
	private $channelResolver;
	private $TPV;
	private $order;
	private $user;
	
	private $sessionManager;
	private $visibleSteps;
	private $defaultStep;
	private $stepNumbers;
	private $defaultStepNumbers;
	
	private $currentStepNumber;
	private $totalStepsNumber;
	
	/**
    * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        $events = [];
		
        return $events;
    }
	
	public function onCheckoutFormSubmit(Event $event) {
		$this->goNext();
	}
	
	/**
	 * Constructs a new CheckoutOrderManager object.
	 *
	 * @param \Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface $chain_checkout_flow_resolver
	 *   The chain checkout flow resolver.
	 */
	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
		$this->cart  = \Drupal::service('gv_fanatics_plus_cart.cart');
		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$this->TPV = \Drupal::service('gv_fanatics_plus_checkout.tpv');
		$this->order = \Drupal::service('gv_fanatics_plus_order.order');
		$this->user = \Drupal::service('gv_fplus_auth.user');
		
		$this->visibleSteps = [
	 		PostPaymentOrderSteps::LOGALTY => [
        		'label' => 'Review data'
      		],
      		PostPaymentOrderSteps::SHIPPING_METHOD_SELECT =>  [
        		'label' => 'Products'
      		],
      		PostPaymentOrderSteps::DOCUMENTS =>  [
        		'label' => 'Post payment'
      		],
      		PostPaymentOrderSteps::SHIPPING_DATA =>  [
        		'label' => 'Payment'
      		],
      		PostPaymentOrderSteps::COMPLETE => [
      			'label' => 'Complete'
      		]
	 	];
		
		$this->defaultStepNumbers = [
			PostPaymentOrderSteps::LOGALTY => 1,
      		PostPaymentOrderSteps::SHIPPING_METHOD_SELECT =>  2,
      		PostPaymentOrderSteps::SHIPPING_DATA =>  3,
      		//PostPaymentOrderSteps::DOCUMENTS =>  4,
      		PostPaymentOrderSteps::COMPLETE => 5
		];
		
		$this->stepNumbers = $this->defaultStepNumbers;
		
		$this->sessionManager = \Drupal::service('user.private_tempstore')->get(static::SESSION_STORAGE_PREFIX);
	}
	
	private function _getDefaultStep() {
		return PostPaymentOrderSteps::SHIPPING_METHOD_SELECT;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function getVisibleSteps() {
	 	return $this->visibleSteps;
	 }

	public function getCurrentStepId() {
		$currentCartID = \Drupal::routeMatch()->getParameter('orderID');;
		
		$previousCartID = $this->sessionManager->get('order_id', NULL);
		if ($previousCartID != $currentCartID) { // New cart, set default state
			$defaultStep =  $this->_getDefaultStep();
			$this->sessionManager->set('order_id', $currentCartID);
			$this->sessionManager->set('current_step_id', $defaultStep);
			return $defaultStep;
		}
		
		return $this->sessionManager->get('current_step_id', $this->_getDefaultStep());
	}
	
	public function setCurrentStepId($newStepId) {
		if (!isset($this->visibleSteps[$newStepId])) {
			return FALSE;
		}
		
		$this->sessionManager->set('current_step_id', $newStepId);
		return TRUE;
	}
	
	public function getPreviousStepId() {
		$currentStepId = $this->sessionManager->get('current_step_id', NULL);
		if (!isset($currentStepId)) {
			return NULL;
		}
		
		$previousNextKeys = previousAndNextKey($this->visibleSteps, $currentStepId);
		return $previousNextKeys[0]; // Previous
	}
	
	public function getNextStepId() {
		$currentStepId = $this->sessionManager->get('current_step_id', NULL);
		if (!isset($currentStepId)) {
			return NULL;
		}
		
		$previousNextKeys = previousAndNextKey($this->visibleSteps, $currentStepId);
		return $previousNextKeys[1]; // Next
	}
	
	public function goNext() {
		$currentStepId = $this->getCurrentStepId();
		
		$availableStepIds = $this->getVisibleSteps();
		$previousNextKeys = previousAndNextKey($availableStepIds, $currentStepId);
		$nextStepId = $previousNextKeys[1];
		if (!isset($nextStepId)) {
			// last step, do nothing
			return FALSE;
		}
		
		$this->setCurrentStepId($nextStepId);
		return TRUE;
	}
	
	public function goPrevious() {
		$currentStepId = $this->getCurrentStepId();
		
		$availableStepIds = $this->getVisibleSteps();
		$previousNextKeys = previousAndNextKey($availableStepIds, $currentStepId);
		$previousStepId = $previousNextKeys[0];
		if (!isset($previousStepId)) {
			// First step, do nothing
			return FALSE;
		}
		
		$this->setCurrentStepId($previousStepId);
		return TRUE;
	}

	public function getPostPaymentStepId($requestedStepId = NULL) {		
		$currentStepId = $this->getCurrentStepId();
		
		$availableStepIds = $this->getVisibleSteps();
		if (!in_array($requestedStepId, array_keys($availableStepIds))) {
			return $currentStepId;
		}
		
		$previousNextKeys = previousAndNextKey($availableStepIds, $currentStepId);
		if ($requestedStepId != $previousNextKeys[0]) {
			return $currentStepId;
		}
		
		return $requestedStepId;
	}
	
	public function isPostPaymentActive() {
		$routeName = \Drupal::routeMatch()->getRouteName();
		if ($routeName == PostPaymentOrderSteps::LOGALTY
			|| $routeName == PostPaymentOrderSteps::SHIPPING_METHOD_SELECT
			|| $routeName == PostPaymentOrderSteps::DOCUMENTS
			|| $routeName == PostPaymentOrderSteps::SHIPPING_DATA
			|| $routeName == PostPaymentOrderSteps::COMPLETE) {
				return TRUE;
		}
			
		return FALSE;
	}
	
	public function getSteps() {
		$currentCartID = \Drupal::routeMatch()->getParameter('orderID');
		$previousCartID = $this->sessionManager->get('order_id', NULL);
		if ($previousCartID != $currentCartID) { // New cart, set default state
			$this->initSteps();
			return $this->stepNumbers;
		}
		
		$this->stepNumbers = $this->sessionManager->get('step_numbers');
		return $this->stepNumbers;
	}
	
	public function getCurrentStepNumber() {
		$currentCartID = \Drupal::routeMatch()->getParameter('orderID');
		$routeName = \Drupal::routeMatch()->getRouteName();
		
		$previousCartID = $this->sessionManager->get('order_id', NULL);
		if ($previousCartID != $currentCartID) { // New cart, set default state
			$this->sessionManager->set('order_id', $currentCartID);
			$this->sessionManager->set('current_step_number', 1);
			return $this->getSteps()[$routeName];
		}
		
		$stepNumber = $this->getSteps()[$routeName];
		if (!isset($stepNumber)) {
			return array_pop(array_reverse($this->getSteps()));;
		}
		
		return $stepNumber;
	}
	
	public function setCurrentStepNumber($stepNumber) {
		$currentCartID = \Drupal::routeMatch()->getParameter('orderID');;
		$this->sessionManager->set('current_step_number', $stepNumber);
	}
	
	public function setStepNumbers($newStepNumbers) {
		$this->sessionManager->set('step_numbers', $newStepNumbers);
	}
	
	public function increaseStepNumber() {
		$currentStepNumber = $this->getCurrentStepNumber();
		++$currentStepNumber;
		$this->setCurrentStepNumber($currentStepNumber);
	}
	
	public function getTotalStepsNumber() {
		return count($this->getSteps());
	}
	
	public function initSteps() {
		$currentCartID = \Drupal::routeMatch()->getParameter('orderID');;
		$previousCartID = $this->sessionManager->get('order_id', NULL);
		$this->sessionManager->set('order_id', $currentCartID);
		
		if ($previousCartID != $currentCartID) { // New cart, set default state
			$order = $this->order->getFromID($currentCartID, TRUE, TRUE);
			$withLogalty = FALSE;
			$pendingData = $order->getPendingData();
			if ($order->SignatureRequired && $pendingData->PendingSignature) {
				$withLogalty = TRUE;
			}
			
			$this->setTotalStepsNumber($withLogalty);
		}
		
	}
	
	public function setTotalStepsNumber($withLogalty = FALSE) {
		if (!$withLogalty) {
			$this->stepNumbers = [
      			PostPaymentOrderSteps::SHIPPING_METHOD_SELECT =>  1,
      			PostPaymentOrderSteps::SHIPPING_DATA =>  2
      			//PostPaymentOrderSteps::COMPLETE => 3
			];
		} else {
			$this->stepNumbers = [
				PostPaymentOrderSteps::LOGALTY => 1,
      			PostPaymentOrderSteps::SHIPPING_METHOD_SELECT =>  2,
      			PostPaymentOrderSteps::SHIPPING_DATA =>  3,
      			//PostPaymentOrderSteps::DOCUMENTS =>  4,
      			//PostPaymentOrderSteps::COMPLETE => 4
			];
		}
		
		$this->sessionManager->set('step_numbers', $this->stepNumbers);
	}
}

?>
