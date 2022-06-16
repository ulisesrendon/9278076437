<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Drupal\gv_fanatics_plus_checkout\Event\CheckoutEvents;
use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;

use Drupal\gv_fanatics_plus_checkout\Ajax\EnableFullscreenLoader;
use Drupal\gv_fanatics_plus_checkout\Ajax\DisableFullscreenLoader;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerCheckoutOptionAjaxCommand;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de selección de producto del proceso de checkout.
 */
class SelectProductForm extends FormBase {

	private $session;
	private $apiClient;
	private $cart;
	private $integrant;
	private $checkoutOrderManager;
	private $dateFormatter;
	private $channelResolver;

	private $destinationUrl;
	private $eventDispatcher;
	
	private $translationService;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fanatics_plus_checkout_select_product';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$user = \Drupal::service('gv_fplus_auth.user');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$eventDispatcher = \Drupal::service('event_dispatcher');
		$dateFormatter = \Drupal::service('date.formatter');
		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->cart = $cart;
		$this->user = $user;
		$this->integrant = $integrant;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->eventDispatcher = $eventDispatcher;
		$this->dateFormatter = $dateFormatter;
		$this->channelResolver = $channelResolver;
		$this->translationService = $translationService;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $destinationUrl = NULL) {
		$request = $this->getRequest();
		$sessionID = $this->session->getIdentifier();

		if (isset($destinationUrl)) {
			$this->destinationUrl = $destinationUrl;
		}

		$currentSalesChannel = $this->channelResolver->resolve();
		$IDServiceType = $currentSalesChannel->getIDServiceType();
		
		$currentRequest = \Drupal::request();
		$overrideCollectiveParam = $currentRequest->query->get('override_collective');
		if ($overrideCollectiveParam != NULL) {
			$overrideCollective = ($overrideCollectiveParam == 1);
			$this->session->setOverrideCollective($overrideCollective);
		} else {
			$overrideCollective = $this->session->overrideCollective();
			if ($overrideCollective) {
				$overrideCollective = TRUE;
			} else {
				$overrideCollective = FALSE;
			}
		}
		
		$isManagingIntegrant = $this->session->isManagingIntegrant();
		$isIntegrantActive = $this->session->isIntegrantActive();
		if ($isManagingIntegrant) {
			$activeIntegrantID = $this->session->getActiveIntegrantClientID();
			$activeIntegrant = NULL;
			
			// TODO: we shouldn't need to filter for the integrant
			$integrants = $this->integrant->listMembers($this->session->getIDClient())->List;
			foreach ($integrants as $integrant) {
				if ($integrant->IntegrantID == $activeIntegrantID) {
					$activeIntegrant = $integrant;
				}
			}
			
			$userProfile = $activeIntegrant;
		} else {
			$userProfile = $this->user->getProfileByID($this->session->getIDUser());
			if ($userProfile->SkiData->HasOverduePayments) {
				$destination_url = Url::fromRoute('gv_fanatics_plus_order.order_history', [], ['query' => ['defaulting' => 1]])->toString();
				return new RedirectResponse($destination_url, 307);
			}
		}
		
		// Generar búsqueda
		$search = $this->apiClient->search()->create($sessionID, $IDServiceType, $currentSalesChannel->getStartSearchDate(), $currentSalesChannel->getEndSearchDate(), $this->session->getActiveIntegrantClientID(), $overrideCollective);
		$searchID = $search->Identifier;
		
		$alreadyBoughtForfait = FALSE;
		$overduePayments = FALSE;
		
		// Comprobar disponibilidad
		$products = [];
		$allThemes = [];
		try {
			$availability = $this->apiClient->availability()->create($sessionID, $searchID, TRUE);
		} catch(\Exception $e) {
			if ($e->getResponse()->getStatusCode() == 409) {
				$availability = new \stdClass();
				$availability->Products = [];	
				$alreadyBoughtForfait = TRUE;
			} else if ($e->getResponse()->getStatusCode() == 402) {
				$availability = new \stdClass();
				$availability->Products = [];	
				$overduePayments = TRUE;
			} else {
				throw $e;
			}
		}
		
		$availabilityProducts = $availability->Products;
		$products = $availabilityProducts;
		foreach ($availabilityProducts as $product) {
			foreach ($product->Themes as $theme) {
				$allThemes[$theme] = $theme;
			}
		}
		
		//$pageTitle = $this->t('Products available to you', [], ['context' => TranslationContext::CHECKOUT]);
		$pageTitle = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.MAIN_TITLE');
		$activeIntegrant = NULL;
		$cart = $this->cart->getCurrentDetail()->Booking;
		if ($isManagingIntegrant) {
			$integrants = $this->integrant->listMembers($cart->IDClient)->List;
			
			foreach ($integrants as $integrant) {
				if ($integrant->IntegrantID == $this->session->getActiveIntegrantClientID()) {
					$activeIntegrant = $integrant;
				}
			}
			
			$pageTitle = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.MAIN_TITLE_INTEGRANT', ['@name' => $activeIntegrant->Name]);
		}
		
		$hasCollective = ((($userProfile->ClubID != NULL && $userProfile->ClubID > 0) || ($userProfile->IDClub != NULL && $userProfile->IDClub > 0)) && strlen($userProfile->ClubIdentification) > 0);
		$userBirthDate = $userProfile->BirthDate;
		$now = new \DateTime();
		$ageDiff = $now->diff(new \DateTime($userBirthDate));
		$age = $ageDiff->y;
		/*if ($age == 19) {
			--$age;
		}*/
		
		/*$products = array_filter($products, function($product) use ($age) {
			$ageFrom = $product->SeasonPassData->AgeFrom;
			$ageTo = $product->SeasonPassData->AgeTo;
			
			if ($age < $ageFrom || $age > $ageTo) {
				return FALSE;
			}
			
			return TRUE;
		});*/
		
		usort($products, function($a, $b) {
			return $a->SeasonPassData->Order > $b->SeasonPassData->Order;
		});
				
		if (count($products) <= 0) {
			$pageTitle = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.NO_PRODUCTS_TITLE');	
		}
		
		$form["product_list_wrapper_head"] = [
		    '#prefix' => '<div class="product-list-header">',
		    '#suffix' => '</div>',
		    '#weight' => -3
		];
		
		$form["product_list_wrapper_head"]['product_list_title'] = [
			'#markup' => '<h1 class="title">' . $pageTitle . '</h1>',
			'#weight' => -3
		];
		
		$form["product_list_wrapper_head"]['product_list_notices'] = [
			'#prefix' => '<div class="product-list-notices">',
			'#suffix' => '</div>',
			'#weight' => -2
		];
		
		if ($isManagingIntegrant) {
			$reviewResidenceDataNotice = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.REVIEW_RESIDENCE_DETAILS');
			$reviewResidenceDataUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($this->session->getActiveIntegrantClientID())]])->toString();
			$reviewResidenceDataBtnLabel = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.EDIT_LABEL');
			$form["product_list_wrapper_head"]['product_list_notices']['review_residence_data_notice'] = [
				'#markup' => '<div class="notice review-residence-data"><span class="label">' . $reviewResidenceDataNotice . '</span><a class="btn" href="' . $reviewResidenceDataUrl . '">' . $reviewResidenceDataBtnLabel .'</a></div>'
			];
		}

		if ($isManagingIntegrant && (!isset($activeIntegrant->Email) || !\Drupal::service('email.validator')->isValid($activeIntegrant->Email))) {
			$noEmailAddressNotice = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.REVIEW_EMAIL', ['@name' => $activeIntegrant->Name]);
			$editIntegrantProfileUrl =  Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($this->session->getActiveIntegrantClientID())]])->toString();
			$editIntegrantBtnLabel = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.ADD_LABEL');
			$form["product_list_wrapper_head"]['product_list_notices']['no_email_notice'] = [
				'#markup' => '<div class="notice no-email"><span class="label">' . $noEmailAddressNotice . '</span><a class="btn" href="' . $editIntegrantProfileUrl . '">' . $editIntegrantBtnLabel .'</a></div>'
			];
		}

		if ($hasCollective && $overrideCollective) {
			$noEmailAddressNotice = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.WITHOUT_COLLECTIVE');
			$editIntegrantProfileUrl =  Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION], ['query' => ['override_collective' => 0]])->toString();
			$editIntegrantBtnLabel = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.ENABLE_COLLECTIVE');

			$form['override_collective_code'] = [
			    '#markup' => '  <div class="collective-products-filter no-selected">
                                    <div class="no-collective">'.$this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.STANDARD_PRODUCTS').'</div>
                                    <a class="" href="' . $editIntegrantProfileUrl . '"><div class="item-toggle"><div class="tooglator"></div></div></a>
                                    <div class="collective"><a class="" href="' . $editIntegrantProfileUrl . '">'.$this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COLLECTIVE_PRODUCTS').'</a></div>
                                </div>'
			];
		} else if ($hasCollective) {
			$noEmailAddressNotice = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.WITH_COLLECTIVE');
			$editIntegrantProfileUrl =  Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION], ['query' => ['override_collective' => 1]])->toString();
			$editIntegrantBtnLabel = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.DISABLE_COLLECTIVE');

			$form['enable_collective_code'] = [
			    '#markup' => '  <div class="collective-products-filter selected">
                                    <div class="no-collective"><a class="" href="' . $editIntegrantProfileUrl . '">'.$this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.STANDARD_PRODUCTS').'</a></div>
                                    <a class="" href="' . $editIntegrantProfileUrl . '"><div class="item-toggle"><div class="tooglator"></div></div></a>
                                    <div class="collective">'.$this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COLLECTIVE_PRODUCTS').'</div>
                                </div>'
			];
		}
		
		$this->getRequest()->getSession()->set('gv_fanatics_plus_checkout_search_id', $searchID);
		$form['product_list_item_container'] = [
				'#prefix' => '<div id="product-list-wrapper"><div id="product-list" class="product-list">',
				'#suffix' => '</div></div>'
		];
		
		foreach ($products as $index => $product) {
			$hasDiscount = FALSE;
			$referencePrice = NULL;
			if (($product->SeasonPassData->Offer || (is_numeric($product->SeasonPassData->ReferencePrice) && $product->SeasonPassData->ReferencePrice > 0)) 
				&& $product->SeasonPassData->ReferencePrice != NULL && $product->SeasonPassData->ReferencePrice > $product->Price) {
				$hasDiscount = TRUE;
				$referencePrice = $product->SeasonPassData->ReferencePrice;
			}
			
			$productThemes = join(',', $product->Themes);
			if (!isset($productThemes) || strlen($productThemes) <= 0) {
				$productThemes = '';
			}
			
			$isIntegrantActive = $this->session->isManagingIntegrant();
			$ownerClientID = $cart->IDClient;
			$productSelected = FALSE;
			foreach ($cart->Services as $service) {
				if ($isIntegrantActive && $service->SeasonPassData->IDClient != $this->session->getActiveIntegrantClientID()) {
					// Do nothing
				} else if (!$isIntegrantActive && $service->SeasonPassData->IDClient != $ownerClientID) {
					// Do nothing
				} else if ($product->IDProduct == $service->SeasonPassData->IDProduct) {
					$productSelected = TRUE;
				}
			}
			
			$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
			$validtyToFormatted = $this->dateFormatter->format(strtotime($product->SeasonPassData->ValidityTo), 'custom', 'j F Y', NULL, $langCode);
			$validityToLabel = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.VALID_UNTIL', ['@date' => $validtyToFormatted]);
			$productMarkup = '';
			$productMarkup .= '<div class="product-list-item ' . ($productSelected ? 'selected' : '') . '" data-product-id="' . $product->IDProduct . '" data-product-code="' . $product->ProductBookingCode . '" data-themes="' . $productThemes .'"><div class="product-list-item--inner"><div class="img-container">' . (isset($product->SeasonPassData->ImageBase64) ? ('<img src="" data-src="' . $product->SeasonPassData->ImageBase64 . '" />') : '<img src="/modules/custom/gv_fplus/img/img-fallback.png"/>') . '</div>';
			$productMarkup .= '<div class="main-body-container"><div class="availability"><span>' . $validityToLabel . '</span></div>';
			$productMarkup .= '<div class="product-name"><h4>' . $product->ProductName . '</h4></div>';
			$productMarkup .= '<div class="product-description">' . $product->ProductDescription . '</div>';
			$productMarkup .= '</div>';
			
			$productMarkup .= '<div class="pricing-info-container">';
			
			if ($hasDiscount) {
				$productMarkup .= '<span class="original-price">' . number_format($referencePrice, 2, ',', '.') . '€</span>';
			}
			
			$productMarkup .= '<a class="btn add-product-btn ' . ($productSelected ? 'selected' : '') . '" href="#" data-product-id="' . $product->IDProduct . '" data-product-code="' . $product->ProductBookingCode . '"><span class="price">' . number_format($product->Price, 2, ',', '.') . '€ </span> • <span class="to-add-label">' . $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.ADD_PRODUCT') . '</span><span class="added-label">' . $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.PRODUCT_ADDED') . '</span></a>';
			$productMarkup .= '</div></div></div>';
			
			$form['product_list_item_container']['product_list_item_' . $index] = [
				'#markup' => $productMarkup,
			];
		}

		// No results behaviour
		if (count($products) <= 0) {
			$form['product_list_item_container']['no_products'] = [
				'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
				. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.NO_PRODUCTS_BODY_1')
				. '</p></div><div class="panel-content"><p>' 
				. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.NO_PRODUCTS_BODY_2') 
				. '</p></div></div></div>'
			];
		}
		
		if ($alreadyBoughtForfait) {
			$form['product_list_item_container']['no_products'] = [
				'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
				. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.SKI_PASS_BOUGHT_CURRENT_SEASON') 
				. '</p></div><div class="panel-content"><p>' 
				. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.NO_PRODUCTS_BODY_2') 
				. '</p></div></div></div>'
			];
		}
		
		if ($overduePayments) {
			$form['product_list_item_container']['no_products'] = [
				'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
				. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.OVERDUE_PAYMENTS_BODY') 
				. '</p></div><div class="panel-content"><p>' . '</p></div></div></div>'
			];
		}
		
		$finalThemes = [];
		$systemThemes = $this->apiClient->core()->getThemes();
		foreach($systemThemes as $systemTheme) {
			if (!isset($allThemes[$systemTheme->Identifier])) {
				continue;
			}
			
			$finalThemes[$systemTheme->Identifier] = [
				'identifier' => $systemTheme->Identifier,
				'label' => $systemTheme->Theme,
				'order' => $systemTheme->Order
			];
		}
		
		if (count($products) > 0 && !$alreadyBoughtForfait) {
			if (count($finalThemes) > 0 && !$currentSalesChannel->hideProductSearchFilter()) {
			    $form['filters'] = [
			        '#prefix' => '<div class="filter-container">',
			        '#suffix' => '</div>',
					'#weight' => -1
				];
				
				$themeOptions = [];
				foreach ($finalThemes as $index => $theme) {
					$themeOptions[$index] = $theme['label'];
				}
				
				$form['filters']['pre_cont']  = [
				    '#prefix' => '<div class="pre_cont hidden">',
				    '#suffix' => '</div>',
			    ];
				
				$form['filters']['pre_cont']['open'] = [
				    '#markup' => '<a class="btn outline">'.t('Filter').'<p class="counter hidden"></p></a>'  
				];
				
				$form['filters']['pre_cont']['close'] = [
				    '#prefix' => '<div class="close-mobile hidden">',
				    '#suffix' => '</div>',
				];
				
				$form['filters']['pre_cont']['close']['text'] = [
				    '#markup' => '<div class="text-close-pre">'.t('Filter products').'</div>',
				];
				
				$form['filters']['pre_cont']['close']['close'] = [
				    '#markup' => '<a class="close-btn btn">'.t('X').'</a>',
				];
				
				$form['filters']['theme'] = [
					'#type' => 'select',
					'#options' => $themeOptions,
					'#empty_option' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.FILTER_BY_LABEL'),
					'#multiple' => TRUE
				];
				
				$form['filters']['post_cont']  = [
				    '#prefix' => '<div class="post_cont hidden">',
				    '#suffix' => '</div>',
				];
				
				$form['filters']['post_cont']['ok'] = [
				    '#markup' => '<a class="btn outline">'. $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.FILTER_BY_APPLY') .'</a>'
				];
			}
			
			$modalBaseUrl = Url::fromRoute('gv_fanatics_plus_checkout.change_product_modal', ['searchid' => $searchID]);
			$form['open_modal'] = [
	      		'#type' => 'link',
	      		'#title' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.OPEN_MODAL'),
	      		'#url' => $modalBaseUrl,
	      		'#attributes' => [
	        		'class' => [
	          			'use-ajax',
	          			'button',
	        		],
	        		'data-base-url' => $modalBaseUrl->toString()
	      		],
	    	];
			
			$form['hidden'] = [];
			$form['hidden']['product_id'] = [
				'#type' => 'textfield',
				'#attributes' => [
					'class' => ['hidden']
				],
				'#required' => FALSE
			];
			
			$form['hidden']['product_code'] = [
				'#type' => 'textfield',
				'#attributes' => [
					'class' => ['hidden']
				],
				'#required' => FALSE
			];

			$form['hidden']['first_tour'] = [
				'#type' => 'checkbox',
				'#default_value' => $this->checkoutOrderManager->isFirstTour()
			];
		}
		
		$form['actions'] = [
			'#prefix' => '<div class="checkout-form-main-actions">',
			'#suffix' => '</div>'
		];
		
		$go_back_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA]);
		if ($isManagingIntegrant) {
			$go_back_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($this->session->getActiveIntegrantClientID())]]);
		}
		
		$form['actions']['go_back'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . $go_back_url->toString() . '">' . $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.EDIT_PROFILE') . '</a>' 
		];
		
		//$form['#prefix'] = '<div id="gv-fanatics-plus-select-products-form-wrapper">';
    	//$form['#suffix'] = '</div>';
				
	   	$form['actions']['submit'] = [
	   		'#type' => 'submit',
  			//'#value' => '<span class="label">' . t('Next: Payment') . '</span><span class="price">' . number_format($cart->SalesAmount, 2, ',', '.') . '€</span>',
  			'#value' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.SUBMIT_BTN_LABEL'),
  			'#ajax' => [
        		//'wrapper' => 'gv-fanatics-plus-select-products-form-wrapper',
        		'callback' => '::selectProductSubmitAjaxCallback',
      		],
      		'#attributes' => [
      			'data-step' => "4",
      			'data-intro' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.TOUR.SUBMIT'),
      			'data-highlightClass' => 'helper-transparent'
      		]
	   	];
		
		if (count($cart->Services) <= 0) {
			$form['actions']['submit']['#disabled'] = TRUE;
			//$form['actions']['submit']['#attributes']['disabled'] = TRUE;
		}
		
		$form['#cache']['contexts'][] = 'session';
		//
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/introjs';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/select_product_form';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/change_product_ajax_commands';
		
		return $form;
	}

	private function _checkIntegrantsNoProducts() {
		$cart = $this->cart->getCurrentDetail()->Booking;
		$integrants = $this->integrant->listMembers($this->session->getIDClient())->List;
		
		if ((count($cart->Services) < (count($integrants) + 1)) || (count($integrants) == 0)) {
			return TRUE;
		}

		return FALSE;
	}

	public function selectProductSubmitAjaxCallback(array &$form, FormStateInterface $form_state) {
		$firstTour = $form_state->getValue('first_tour');
		$response = new AjaxResponse();
		
		if ($this->_checkIntegrantsNoProducts() && $firstTour) {
			
			$integrantsReminderModalForm = \Drupal::service('form_builder')->getForm('Drupal\gv_fanatics_plus_checkout\Form\IntegrantsReminderModalForm');
			$response->addCommand(new InvokeCommand('#edit-first-tour', 'prop', ['checked', FALSE]));
			$response->addCommand(new DisableFullscreenLoader(NULL));
			$response->addCommand(new OpenModalDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.TOUR.MODAL_HEADER'), $integrantsReminderModalForm, ['width' => '800']));	
			
			return $response;
		}
		
		//$response->addCommand(new TagManagerCheckoutOptionAjaxCommand(NULL, 2));
		
		if (isset($this->destinationUrl)) {
			$command = new RedirectCommand($this->destinationUrl);
    		$response->addCommand($command);
			$response->addCommand(new EnableFullscreenLoader(NULL));
		}
		
		$response->addCommand(new TagManagerCheckoutOptionAjaxCommand(NULL, 2));
		
		return $response;
	}

	public function setProduct(array &$form, FormStateInterface $form_state) {}
	
	public function setProductCallback(array &$form, FormStateInterface $form_state) {
		$selectedProductId = $form_state->getValue('product_id');
		$selectedProductCode = $form_state->getValue('product_code');
		$searchID = $this->getRequest()->getSession()->get('gv_fanatics_plus_checkout_search_id');
		
		//for the time being, delete all products
		$cartContents = $this->cart->getCurrentDetail()->Booking;
		foreach ($cartContents->Services as $index => $service) {
			$bookingServiceID = $service->Identifier;
			$this->cart->removeBookingService($bookingServiceID);
		}

		$result = $this->cart->addBookingService($searchID, $selectedProductCode);
		return $form['product_list_item_container'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		// one can proceed if:
		// At least one service is selected in the cart
		$cart = $this->cart->getCurrentDetail()->Booking;
		if (count($cart->Services) <= 0) {
			$form_state->setErrorByName('', $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.NO_PRODUCTS_ERROR'));
		}
		
		$bookingReferral = $this->cart->getBookingReferral();
		if (isset($bookingReferral) && strlen($bookingReferral) > 0 && strlen($bookingReferral) != 6) {
			$form_state->setErrorByName('', $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.INVALID_REFERENCE_CODE'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$firstTour = $form_state->getValue('first_tour');
		if ($this->_checkIntegrantsNoProducts() && $firstTour) { // && first_tour
			return;
		}
		
		$this->eventDispatcher->dispatch(CheckoutEvents::SELECT_PRODUCTS_FORM_SUBMIT);
		if (isset($this->destinationUrl)) {
			$form_state->setResponse( new TrustedRedirectResponse($this->destinationUrl, 307) );
		}
	}
}

?>