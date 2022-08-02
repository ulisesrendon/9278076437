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

        $moreInfoIcon = '<svg width="36" height="46" viewBox="0 0 36 46" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M4.33948 16.2479H10.752C11.1104 16.2479 11.4007 16.5382 11.4007 16.8966V17.3686C12.5749 17.0994 14.8827 16.4149 16.3538 14.944C17.768 13.4341 18.4963 11.4085 18.3681 9.34235C18.3681 8.40171 19.4239 7.41565 20.5835 7.27291C21.3312 7.18209 23.18 7.34103 24.0542 10.8376V10.8393C24.3639 12.4935 24.4012 14.1883 24.1677 15.8572H28.3826C29.4465 15.8426 30.4456 16.3599 31.0489 17.2357C31.8468 18.3661 32.481 20.6139 30.9759 24.858L29.226 31.1684V31.17C28.9016 32.9459 27.3528 34.2368 25.5477 34.2368H20.8446C18.5675 34.2368 13.8595 33.8233 11.4008 32.6264V33.4616C11.4008 33.6335 11.3327 33.7989 11.2111 33.9206C11.0894 34.0422 10.924 34.1103 10.7521 34.1103H4.33957C4.16766 34.1103 4.00386 34.0422 3.88221 33.9206C3.76057 33.799 3.69084 33.6335 3.69084 33.4616V16.8966C3.69084 16.7247 3.76057 16.5593 3.88221 16.4377C4.00385 16.316 4.16765 16.2479 4.33957 16.2479L4.33948 16.2479ZM20.8447 32.9395H25.5478C26.7463 32.933 27.7665 32.0621 27.9611 30.8798L29.7386 24.469C30.7636 21.574 30.8544 19.2095 29.9883 17.9851C29.6283 17.4548 29.025 17.1418 28.3827 17.1547H23.425C23.2337 17.1547 23.052 17.0704 22.9288 16.9244C22.8055 16.7801 22.7536 16.5871 22.7844 16.399C22.7893 16.3665 23.3066 13.2024 22.7942 11.154C22.366 9.4381 21.6329 8.4877 20.7912 8.55745H20.7929C20.3031 8.598 19.8733 8.89803 19.6657 9.34239C19.7922 11.7524 18.9262 14.1074 17.2719 15.862C15.4296 17.7044 12.5928 18.4537 11.4008 18.6985V31.1295C12.8864 32.2956 17.82 32.9395 20.8446 32.9395L20.8447 32.9395ZM4.98841 32.813H10.1035V17.5455H4.98841V32.813ZM21.641 3.90093V1.14872C21.641 0.790304 21.3507 0.5 20.9922 0.5C20.6338 0.5 20.3435 0.790304 20.3435 1.14872V3.90093C20.3435 4.25935 20.6338 4.54965 20.9922 4.54965C21.3507 4.54965 21.641 4.25935 21.641 3.90093ZM16.5338 7.08131C16.623 6.93372 16.649 6.75695 16.6084 6.5899C16.5662 6.42286 16.4608 6.27851 16.3132 6.19093L13.9551 4.77184C13.6486 4.58695 13.2497 4.68588 13.0648 4.99241C12.8799 5.30055 12.9788 5.69788 13.2853 5.88277L15.6434 7.30186C15.791 7.39106 15.9678 7.41701 16.1349 7.37646C16.3019 7.3343 16.4462 7.22726 16.5338 7.08129L16.5338 7.08131ZM28.9195 4.99244C28.7347 4.68592 28.3357 4.58699 28.0292 4.77188L25.6711 6.19097C25.3645 6.37586 25.2656 6.77318 25.4505 7.08133C25.6338 7.38785 26.0327 7.48678 26.3409 7.3019L28.699 5.8828H28.6974C28.8449 5.79522 28.952 5.65089 28.9925 5.48384C29.0347 5.31679 29.0087 5.14002 28.9195 4.99243L28.9195 4.99244Z" fill="#4C7AA8"/>
<ellipse opacity="0.15" cx="18" cy="27.6962" rx="17.8055" ry="17.8056" fill="#4C7AA8"/>
</svg>
';
		
		$form['payment_method_descriptors'] = [
			'#prefix' => '<div class="payment-method-descriptors">',
			'#suffix' => '</div></div>'
		];

		foreach($paymentMethodOptions as $index => $paymentMethod) {
			$form['payment_method_descriptors']['payment-method-' . $index] = [
                '#type' => 'inline_template',
				'#template' => '
				<div class="payment-method-descriptors--inner payment-method-' . $index . '" data-payment-method-id="' . $index . '">
                    <div class="alert alert-primary btnb-custom-alert" role="alert">
                        <div class="payment-method-title">
                            <span class="payment-description-icon">'.$moreInfoIcon.'</span>
                            <div class="payment-method-description">'.$this->translationService->translate('CHECKOUT_PAYMENT.TITLE.'.$index).'</div>
                            <span class="payment-description-more-info">más info</span>
                        </div>
                        <div class="payment-description-body">'.$this->translationService->translate('CHECKOUT_PAYMENT.DESCRIPTION.'.$index).'</div>
                    </div>
				</div>'
			];
		}

//        $form['payment_method_descriptors']['test'] = [
//            '#type' => 'item',
//            '#markup' => 'ESTO ES UNA ALERTA DE PRUEBA '.$this->session->getIdentifier().''
//        ];
		
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
		} else if ($currentChannel->isPal()) {
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
			$form_state->setResponse( new TrustedRedirectResponse($this->destinationUrl, 307) );
		}
	}
}

?>