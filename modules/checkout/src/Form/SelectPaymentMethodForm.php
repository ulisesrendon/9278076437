<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Drupal\gv_fanatics_plus_checkout\Event\CheckoutEvents;
use Drupal\gv_fanatics_plus_checkout\Event\PaymentMethodSelectedEvent;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de selección de método de pago del proceso de checkout.
 */
class SelectPaymentMethodForm extends FormBase {

	private $session;
	private $cart;
	private $user;
	private $checkoutOrderManager;
	private $channelResolver;
	
	private $eventDispatcher;
	private $destinationUrl;

	private $translationService;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fanatics_plus_checkout_select_payment_method';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$user = \Drupal::service('gv_fplus_auth.user');
		$eventDispatcher = \Drupal::service('event_dispatcher');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$this->session = $session;
		$this->cart = $cart;
		$this->user = $user;
		$this->eventDispatcher = $eventDispatcher;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->channelResolver = $channelResolver;
		$this->translationService = $translationService;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $destinationUrl = NULL) {

		$request = $this->getRequest();
		$sessionID = $this->session->getIdentifier();
		
		$cart = $this->cart->getCurrentDetail()->Booking;
		if (count($cart->Services) <= 0) {
			//$this->checkoutOrderManager->goPrevious();
			return new RedirectResponse(Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION])->toString(), 307);
		}
		
		if (isset($destinationUrl)) {
			$this->destinationUrl = $destinationUrl;
		}
				
		$paymentMethods = $this->cart->getAvailablePaymentMethods();
		$paymentMethodOptions = [];
		$paymentMethodDescriptions = [];
		$defaultPaymentMethod = NULL;
		foreach ($paymentMethods->List as $index => $paymentMethod) {
			if ($index == 0) {
				$defaultPaymentMethod = $paymentMethod->Identifier;
			}

			$paymentMethodOptions[$paymentMethod->Identifier] = ucfirst(mb_strtolower($paymentMethod->PaymentMethod));
			$paymentMethodDescriptions[$paymentMethod->Identifier] = $paymentMethod->Description;
		}
		
		$form['title_container'] = [ 
			'#markup' => '<div class="title-container"><h1>' 
			. $this->translationService->translate('CHECKOUT_PAYMENT.MAIN_TITLE') 
			. '</h1></div>'
		];
		
		$form['payment_method'] = [
			'#prefix' => '<div class="container payment-container">',
			'#type' => 'radios',
			'#options' => $paymentMethodOptions,
			'#default_value' => $defaultPaymentMethod,
			'#required' => TRUE
		];
		
		$form['payment_method_descriptors'] = [
			'#prefix' => '<div class="payment-method-descriptors">',
			'#suffix' => '</div></div>'
		];

		foreach($paymentMethodOptions as $index => $paymentMethod) {
			$form['payment_method_descriptors']['payment-method-' . $index] = [
				'#prefix' => '<div class="payment-method-descriptors--inner payment-method-' . $index . '" data-payment-method-id="' . $index . '">',
				'#suffix' => '</div>',
				'#markup' => '<div class="payment-method-description">' . $paymentMethodDescriptions[$index] . '</div>'
			];
		}

        $form['test'] = [
            '#type' => 'item',
            '#markup' => '<div class="alert alert-primary" role="alert">ESTO ES UNA ALERTA DE PRUEBA '.$this->session->getIdentifier().'</div>'
        ];
		
		$form['actions'] = [
			'#prefix' => '<div class="checkout-form-main-actions">',
			'#suffix' => '</div>'
		];
		
		$currentChannel = $this->channelResolver->resolve();
		$reservationConditionsUrl = $this->t('I accept the <a class="terms-conditions" href="https://www.grandvalira.com/en/sales-conditions-season-ski-pass" target="_blank">booking conditions</a>', [], ['context' => TranslationContext::CHECKOUT]);
		if ($currentChannel->isPlus()) {
			$reservationConditionsUrl = $this->t('I accept the <a class="terms-conditions" href="https://www.grandvalira.com/en/sales-conditions-plus-ski-pass" target="_blank">booking conditions</a>', [], ['context' => TranslationContext::CHECKOUT]);
		} else if ($currentChannel->isTemporadaOA()) {
			$reservationConditionsUrl = $this->t('I accept the <a class="terms-conditions" href="https://www.ordinoarcalis.com/en/season-sale-conditions-clause" target="_blank">booking conditions</a>', [], ['context' => TranslationContext::CHECKOUT]);
		}
		
		$form['actions']['terms_and_conditions'] = [
	   		'#type' => 'checkbox',
	   		'#title' => $reservationConditionsUrl,
	   		'#return_value' => 1,
	   		'#default_value' => 0,
		];
		
		$go_back_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION]);
		
		$form['actions']['btn_actions'] = [
		    '#prefix' => '<div class="checkout-form-main-principal-actions">',
		    '#suffix' => '</div>'
		];
		$form['actions']['btn_actions']['go_back'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . $go_back_url->toString() . '">' 
			. $this->t('CHECKOUT_PAYMENT.GO_BACK_BTN_LABEL') 
			. '</a>' 
		];
		
		$form['actions']['btn_actions']['submit'] = [
	   		'#type' => 'submit',
  			'#value' => $this->translationService->translate('CHECKOUT_PAYMENT.SUBMIT_BTN_LABEL') . '        ' . number_format($this->cart->getCurrentDetail()->Booking->SalesAmount, 2, ',', '.') . '€'
	   	];
		
		$form['#cache']['contexts'][] = 'session';

		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/select_payment_method_form';
		
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$legalConsent = $form_state->getValue('terms_and_conditions');
		if (!$legalConsent) {
			$form_state->setErrorByName('terms_and_conditions', $this->translationService->translate('CHECKOUT_PAYMENT.TC_REQUIRED'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$IDPaymentMethod = $form_state->getValue('payment_method');
		$IDBooking = $this->cart->getCurrent()->Identifier;
		$IDSession = $this->session->getIdentifier();
		$this->eventDispatcher->dispatch(CheckoutEvents::SELECT_PAYMENT_METHOD_FORM_SUBMIT, new PaymentMethodSelectedEvent($IDSession, $IDBooking, $IDPaymentMethod));
		if (isset($this->destinationUrl)) {
			//$form_state->setResponse( new TrustedRedirectResponse($this->destinationUrl, 307) );
		}
	}
}

?>