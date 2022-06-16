<?php

namespace Drupal\gv_fanatics_plus_cart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\gv_fanatics_plus_order\Order;
use Drupal\Core\Url;

/**
 * Representa el formulario de edición de carrito
 */
class CartBlockForm extends FormBase {

	private $session;
	private $apiClient;
	private $cart;
	private $checkoutOrderManager;
	private $integrant;
	private $user;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fanatics_plus_cart_block_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$user = \Drupal::service('gv_fplus_auth.user');
		$image = \Drupal::service('gv_fplus_auth.image');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->cart = $cart;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->integrant = $integrant;
		$this->user = $user;
		$this->image = $image;
  	}

	protected function _buildCartMarkup() {
		$cartService = \Drupal::service('gv_fanatics_plus_cart.cart');
		$sessionService = \Drupal::service('gv_fplus.session');
		$integrantService = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$imageService = \Drupal::service('gv_fplus_auth.image');
		$userService = \Drupal::service('gv_fplus_auth.user');
		$activeSalesChannel = \Drupal::service('gv_fplus.channel_resolver')->resolve();
		
		$cart = $cartService->getCurrentDetail()->Booking;
		$ownerClientID = $sessionService->getIDClient();
		
		foreach ($cart->Services as $index => $service) {
			$bookingServiceID = $service->Identifier;
			$clientID = $service->SeasonPassData->IDClient;
			if ($clientID == $ownerClientID) {
				$IDUser = $sessionService->getIDUser();
				$profile = $imageService->getBySessionID($sessionService->getIdentifier());
				$profile->IsOwner = TRUE;
			} else {
				$integrantProfile = $userService->getUserProfileByClientID($clientID, TRUE);
				$integrantProfile->ImageBase64 = $integrantProfile->Image;
				if (!isset($integrantProfile->ImageBase64)) {
					//$integrantProfile->ImageBase64 = Order::getDefaultUserAvatar();
				}

				$profile = $integrantProfile;
				$profile->IsOwner = FALSE;
			}
			
			$insurances = $cartService->getSeasonPassInsurances($bookingServiceID);
			$cart->Services[$index]->AvailableInsurances = $insurances;
			$cart->Services[$index]->UserData = $profile;
			foreach ($insurances as $insurance) {}
		}
		
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$isCartEditable = $cartService->isEditable($checkoutOrderManager->getCurrentStepId());
		
		$bookingReferral = $cartService->getBookingReferral();
		
		$memberGetMemberUrl = Url::fromRoute('gv_fanatics_plus_checkout.member_get_member_modal')->toString();
		
		$renderable = [
			'#theme' => 'gv_fanatics_plus_cart_block', 
			'#cart' => $cart,
			'#can_edit' => $isCartEditable,
			'#member_get_member_active' => $activeSalesChannel->isMemberGetMemberActive(),
			'#booking_referral' => $bookingReferral,
			'#member_get_member_url' => $memberGetMemberUrl
		];
		
		$cartMarkup = \Drupal::service('renderer')->renderPlain($renderable);
		return $cartMarkup;
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$cartMarkup = $this->_buildCartMarkup();
		$form['cart_markup'] = [
			'#prefix' => '<div id="cart-form-wrapper">',
			'#suffix' => '</div>',
			'#markup' => $cartMarkup,
		];
		
		$form['hidden'] = [];
		$form['hidden']['product_id'] = [
			'#type' => 'textfield',
			'#attributes' => [
				'class' => ['hidden']
			]
		];
		
		$form['hidden']['delete_product'] = [
      		'#type' => 'button',
     		'#value' => $this->t('Delete product'),
      		'#ajax' => [
        		'callback' => '::deleteProductCallback',
        		'wrapper' => 'cart-form-wrapper'
      		]
    	];
		
		$form['hidden']['referral'] = [
			'#type' => 'textfield',
			'#attributes' => [
				'class' => ['hidden']
			],
			'#ajax' => [
        		'callback' => '::saveBookingReferral',
        		'wrapper' => 'cart-form-wrapper',
        		'event' => 'blur'
      		]
		];
		
		$form['load_form'] = [
        	'#type' => 'button',
        	'#value' => $this->t('Refresh cart'),
        	'#ajax' => [
          		'wrapper' => 'cart-form-wrapper',
          		'callback' => '::refresh',
        	],
      	];
		
		$form['#cache']['contexts'][] = 'session';
		$form['#attached']['library'][] ='gv_fanatics_plus_cart/cart_block';

		return $form;
	}

	public function refresh(array &$form, FormStateInterface $form_state) {
		return $form['cart_markup'];
	}

	public function saveBookingReferral(array &$form, FormStateInterface $form_state) {
		$bookingReferral = $form_state->getValue('referral');
		if (!isset($bookingReferral) || $bookingReferral == '') {
			return $form['cart_markup'];
		}
		
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$cart->saveBookingReferral(strtoupper($bookingReferral));
		
		$form['cart_markup']['#markup'] = $this->_buildCartMarkup();
		return $form['cart_markup'];
	}
	
	public function deleteProductCallback(array &$form, FormStateInterface $form_state) {
		$productId = $form_state->getValue('product_id');
		if (isset($productId) && $productId != "") {
			$cartService = \Drupal::service('gv_fanatics_plus_cart.cart');
			$response = $cartService->removeBookingService($productId);
		}
		
		$form['cart_markup']['#markup'] = $this->_buildCartMarkup();
		return $form['cart_markup'];
	}
	
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {}
	
	/**
   	* {@inheritdoc}
   	*/
  	public function submitForm(array &$form, FormStateInterface $form_state) {}
}

?>
