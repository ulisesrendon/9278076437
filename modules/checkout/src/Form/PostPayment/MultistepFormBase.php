<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Clase base para implementar formularios multipaso del proceso de post-pago
 */
abstract class MultistepFormBase extends FormBase {

	/**
	 * @var \Drupal\user\PrivateTempStoreFactory
	 */
	protected $tempStoreFactory;

	/**
	 * @var \Drupal\Core\Session\SessionManagerInterface
	 */
	private $sessionManager;

	/**
	 * @var \Drupal\Core\Session\AccountInterface
	 */
	private $currentUser;

	/**
	 * @var \Drupal\user\PrivateTempStore
	 */
	protected $store;
	
	protected $cart;
	protected $checkoutOrderManager;
	protected $postPaymentOrderManager;
	protected $order;
	protected $apiClient;
	protected $translationService;
	
	protected $currentOrderID;
	protected $currentStepNumber;
	protected $currentStepLabel;
	protected $totalSteps;
	protected $destinationUrl;
	
	protected $formTitle;

	private $storeKeyPrefix;

	public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user, $cart, $checkoutOrderManager, $apiClient, $order, $postPaymentOrderManager, $translationService) {
		$this -> tempStoreFactory = $temp_store_factory;
		$this -> sessionManager = $session_manager;
		$this -> currentUser = $current_user;
		
		$this -> cart = $cart;
		$this -> checkoutOrderManager = $checkoutOrderManager;
		$this -> postPaymentOrderManager = $postPaymentOrderManager;
		$this -> order = $order;
		$this -> apiClient = $apiClient;
		$this -> translationService = $translationService;
		
		$this -> store = $this -> tempStoreFactory -> get('gv_fanatics_plus_checkout');
		$this -> formTitle = 'Tus datos personales';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
			$container -> get('user.private_tempstore'), 
			$container -> get('session_manager'), 
			$container -> get('current_user'),
			$container -> get('gv_fanatics_plus_cart.cart'),
			$container -> get('gv_fanatics_plus_checkout.checkout_order_manager'),
			$container -> get('gv_fplus_dbm_api.client'),
			$container -> get('gv_fanatics_plus_order.order'),
			$container -> get('gv_fanatics_plus_checkout.post_payment_order_manager'),
			$container -> get('gv_fanatics_plus_translation.interface_translation')
		);
	}

	protected function _buildFormHeaderMarkup($bookingLocator = NULL) {
		$markup = '<div class="title-container"><div class="radial-progress-bar"><div class="circle"><div class="fill"><div class="paso"><div class="text"><span class="current-step">';
		$markup .= $this->currentStepNumber .  '</span><span>/</span><span class="total-steps '.($this->currentStepNumber == $this->totalSteps ? 'comp' : '').'">';
		$markup .= $this->totalSteps . '</span></div></div></div></div></div><div class="title-container"><h1>';
		$markup .= $this->translationService->translate($this->formTitle) . '</h1>';
		
		if ($bookingLocator != NULL) {
			$markup .= '<span class="localizator">' . $bookingLocator . '</span>';
		}

		$markup .= '</div></div>';

		return $markup;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		
		$this->currentOrderID = $currentOrderID;
		$this->currentStepNumber = $currentStepNumber;
		$this->totalSteps = $totalSteps;
		$this->destinationUrl = $destinationUrl;
		
		$currentOrderID = \Drupal::routeMatch()->getParameter('orderID');
		$bookingLocator = NULL;
		if ($currentOrderID != NULL) {
			$booking = $this->order::getFromID($currentOrderID, FALSE, FALSE);
			
			if (isset($booking)) {
				$bookingLocator = $booking->Booking->BookingLocator;
			}
		}
		
		$form['title_container'] = [
			'#markup' => $this->_buildFormHeaderMarkup($bookingLocator)
		];
		
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array('#type' => 'submit', '#value' => $this -> t('Submit'), '#button_type' => 'primary', '#weight' => 10, );

		return $form;
	}

  /**
   * Método auxiliar para establecer un prefijo del storage
   */
	protected function setStoreKeyPrefix($prefix) {
		if (isset($prefix)) {
			$this -> storeKeyPrefix = $prefix;
		} else {
			$this -> storeKeyPrefix = '';
		}

		$this -> store = $this -> $tempStoreFactory -> get($this -> storeKeyPrefix);
	}

  /**
   * Método auxiliar para setear un valor del storage interno
   * 
   * @param $key índice clave a definir
   * @param $value valor a definir
   */
	protected function storeSet($key, $value) {
		$keyPrefix = isset($this -> storeKeyPrefix) ? $this -> storeKeyPrefix : '';
		return $this -> store -> set($this -> storeKeyPrefix . $key, $value);
	}

  /**
   * Método auxiliar para retornar un valor del storage interno.
   * 
   * @param $key clave a consultar
   */
	protected function storeGet($key) {
		$keyPrefix = isset($this -> storeKeyPrefix) ? $this -> storeKeyPrefix : '';
		return $this -> store -> get($keyPrefix . $key);
	}

  /**
   * Método auxiliar para borrar claves del storage interno
   * 
   * @param $keys claves a borrar
   */
	protected function deleteStoreKeys($keys = []) {
		foreach ($keys as $key) {
			$this -> store -> delete($key);
		}
	}

}

?>
