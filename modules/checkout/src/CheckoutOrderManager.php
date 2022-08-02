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

use Drupal\gv_fplus\TranslationContext;

/**
 * @param mixed[]    $phpArray
 * @param string|int $key
 *
 * @return array<int, int|string|null> of 2 elements, first one being the previous key, `null` if not found, second one being the next key, `null` if not found
 *
 * @throws AssertionFailedException if the provided $key is not a string or an integer
 */
function previousAndNextKey(array $phpArray, $key) : array {
    
    $keys     = array_keys($phpArray);
    $position = array_search($key, $keys, true);
    
    if (false === $position) {
        return [null, null];
    }
    
    return [
        $keys[$position - 1] ?? null,
        $keys[$position + 1] ?? null,
    ];
}

final class CheckoutOrderSteps {
	const PROFILE_DATA = 'profile-data';
	const PRODUCT_SELECTION = 'select-products';
	const DOCUMENTS = 'need-documents';
	const RECHARGES = 'recharges';
	const PAYMENT = 'payment';
	const POST_PAYMENT = 'post-payment';
	const COMPLETE = 'complete';
}

/**
 * Entidad que gestiona el estado de checkout del carrito así como la selección de integrantes activos
 */
class CheckoutOrderManager implements CheckoutOrderManagerInterface, EventSubscriberInterface {
	
	const ENCRYPTION_KEY = 'mfZEbljN90^$e4OE';
	const ENCRYPTION_METHOD = 'aes-128-ctr';
	const SESSION_STORAGE_PREFIX = 'gv_fanatics_plus_checkout.order_manager';
	
	private $session;
	private $cart;
	private $channelResolver;
	private $TPV;
	private $order;
	private $user;
	
	private $sessionManager;
	private $visibleSteps;
	private $defaultStep;
	
	public static function encrypt($input) {
		$enc_key = openssl_digest(CheckoutOrderManager::ENCRYPTION_KEY, 'SHA256', TRUE);
  		$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(CheckoutOrderManager::ENCRYPTION_METHOD));
		$crypted_token = openssl_encrypt($input, CheckoutOrderManager::ENCRYPTION_METHOD, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
  		unset($enc_key, $enc_iv);
		return $crypted_token;
	}
	
	public static function decrypt($output) {		
		list($crypted_token, $enc_iv) = explode("::", $output);;
  		$enc_key = openssl_digest(CheckoutOrderManager::ENCRYPTION_KEY, 'SHA256', TRUE);
  		$token = openssl_decrypt($crypted_token, CheckoutOrderManager::ENCRYPTION_METHOD, $enc_key, 0, hex2bin($enc_iv));
 		unset($crypted_token, $cipher_method, $enc_key, $enc_iv);
		return $token;
	}
	
	/**
    * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        $events = [
        	AuthEvents::RESIDENCE_DATA_FORM_SUBMIT => 'onResidenceDataFormSubmit',
        	CheckoutEvents::SELECT_PRODUCTS_FORM_SUBMIT => 'onCheckoutFormSubmit',
        	CheckoutEvents::SELECT_PAYMENT_METHOD_FORM_SUBMIT => 'onSelectPaymentMethod',
        	KernelEvents::REQUEST => [['onIntegrantSwitch', -1]]
        ];
		
        return $events;
    }
	
	public function onIntegrantSwitch(GetResponseEvent $event) {
		
		$activeChannel = $this->channelResolver->resolve();
		if (!isset($activeChannel) || \Drupal::service('router.admin_context')->isAdminRoute()) {
			return;
		}
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET') {
			return;
		}

		$switchIntegrant = $currentRequest->query->get('switch-integrant');
		$switchOwner = $currentRequest->query->get('switch-owner');
		$registerNewIntegrant = $currentRequest->query->get('register-new-integrant');
		if (!$switchIntegrant && !$switchOwner && !$registerNewIntegrant) {
			if (isset($registerNewIntegrant) && $registerNewIntegrant != NULL && $registerNewIntegrant == 0) {
				$this->session->refresh(FALSE, FALSE, FALSE, NULL);
			}

			// DO NOTHING
			return;
		}
				
		$integrantID = $currentRequest->query->get('integrant-client-id');
		$decryptedIntegrantID = $this->decrypt($integrantID);

		if ($switchOwner) {
			$this->session->refresh(FALSE, FALSE, FALSE);
		} else if ($switchIntegrant && $decryptedIntegrantID) {
			$this->session->refresh(FALSE, TRUE, FALSE, $decryptedIntegrantID);
		} else if ($registerNewIntegrant) {
			$this->session->refresh(FALSE, FALSE, TRUE, NULL);
		}
	}
	
	public function onResidenceDataFormSubmit(Event $event) {
		$isCreatingIntegrant = $event->isCreatingIntegrant();
		$isManagingIntegrant = $event->isManagingIntegrant();
		
		if ($isCreatingIntegrant) {
			$IDIntegrant = $event->getIDIntegrant();
			
			// refresh session with new IDIntegrant
			$this->session->refresh(FALSE, TRUE, FALSE, $IDIntegrant);	
		}
		
		$this->goNext();
	}
	
	public function onCheckoutFormSubmit(Event $event) {
		$this->goNext();
	}
	
	public function onSelectPaymentMethod(PaymentMethodSelectedEvent $event) {
		$IDSession = $event->getIDSession();
		$IDBooking = $event->getIDBooking();
		$IDPayment = $event->getIDPayment();
		
		$bookingReferral = $this->cart->getBookingReferral();
		try {
			$confirmResponse = $this->TPV->confirmBooking($IDSession, $IDPayment, $bookingReferral);
			$finalBooking = $this->order->getFromID($IDBooking, FALSE, TRUE);
			
			$targetPaymentID = array_pop(array_reverse($finalBooking->Payments))->Identifier;
			
			$urlOK = $this->TPV->getOKUrl($IDBooking);
			$urlKO = $this->TPV->getKOUrl($targetPaymentID);
			
			$paymentResult = $this->TPV->pay($IDSession, $IDBooking, $targetPaymentID, $urlOK, $urlKO);
			
			$URLTPV = $paymentResult->URLTPV;
			
			$this->cart->deleteBookingReferral();
			
			$response = new TrustedRedirectResponse($URLTPV, 307);
			$response->send();
			\Drupal::service('page_cache_kill_switch')->trigger();
		} catch(\Exception $e) {
			ksm($e, $e->getResponse());
			if ($e->getResponse()->getStatusCode() == 409) {
				\Drupal::messenger()->addMessage(t('There are pending payments or profile updates for this order.', [], ['context' => TranslationContext::PROFILE_DATA]), 'error');
			} else {
				throw $e;
			}
		}
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
	 		CheckoutOrderSteps::PROFILE_DATA => [
        		'label' => 'CHECKOUT_PROGRESS.REVIEW_DATA',
				'hidden' => FALSE
      		],
      		CheckoutOrderSteps::PRODUCT_SELECTION =>  [
        		'label' => 'CHECKOUT_PROGRESS.SELECT_PRODUCTS',
				'hidden' => FALSE
      		],
			CheckoutOrderSteps::DOCUMENTS => [
				'label' => 'CHECKOUT_PROGRESS.DOCUMENTS',
				'hidden' => TRUE
			],
			CheckoutOrderSteps::RECHARGES => [
				'label' => 'CHECKOUT_PROGRESS.RECHARGES',
				'hidden' => TRUE
			],
      		CheckoutOrderSteps::PAYMENT =>  [
        		'label' => 'CHECKOUT_PROGRESS.PAYMENT',
				'hidden' => FALSE
      		],
      		CheckoutOrderSteps::POST_PAYMENT => [
      			'label' => 'CHECKOUT_PROGRESS.POST_PAYMENT',
				'hidden' => FALSE
      		]
	 	];
		
		$this->defaultStep = CheckoutOrderSteps::PROFILE_DATA;
		
		$this->sessionManager = \Drupal::service('user.private_tempstore')->get(static::SESSION_STORAGE_PREFIX);
	}
	
	private function _getDefaultStep() {
		$profileDataComplete = $this->user->isProfileDataComplete($this->session->getEmail());
		if (!$profileDataComplete) {
			return CheckoutOrderSteps::PROFILE_DATA;
		}
		
		return CheckoutOrderSteps::PRODUCT_SELECTION;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function getVisibleSteps() {
	 	return $this->visibleSteps;
	 }

	public function getCurrentStepId() {
		$currentCart = $this->cart->getCurrent();
		$currentCartID = $currentCart->Identifier;
		
		$previousCartID = $this->sessionManager->get('order_id', NULL);
		if ($previousCartID != $currentCartID) { // New cart, set default state
			$defaultStep =  $this->_getDefaultStep();
			$this->sessionManager->set('order_id', $currentCartID);
			$this->sessionManager->set('current_step_id', $defaultStep);
			$this->sessionManager->set('first_tour', TRUE);
			return $defaultStep;
		}
		
		return $this->sessionManager->get('current_step_id', $this->_getDefaultStep());
	}
	
	public function isFirstTour() {
		$firstTour = $this->sessionManager->get('first_tour');
		if ($firstTour === NULL) {
			$this->sessionManager->set('first_tour', TRUE);
			return TRUE;
		}
		
		return $firstTour;
	}
	
	public function disableFirstTour() {
		$this->sessionManager->set('first_tour', FALSE);
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

    /**
	* {@inheritdoc}
	*/
	public function getCheckoutStepId($requestedStepId = NULL) {
		$currentStepId = $this->getCurrentStepId();
		
		$availableStepIds = $this->getVisibleSteps();
		
		if (!in_array($requestedStepId, array_keys($availableStepIds))) {
			return CheckoutOrderSteps::PRODUCT_SELECTION;
		}
		
		$previousNextKeys = previousAndNextKey($availableStepIds, $currentStepId);
		if ($requestedStepId != $previousNextKeys[0] && $requestedStepId != CheckoutOrderSteps::PRODUCT_SELECTION && $requestedStepId != CheckoutOrderSteps::PROFILE_DATA) {
			return $currentStepId;
		}
		
		return $requestedStepId;
	}
}

?>
