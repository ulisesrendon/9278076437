<?php

namespace Drupal\gv_fanatics_plus_checkout\Plugin\Block;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementa el bloque de selección / creación de integrantes en el proceso de checkout.
 *
 * @Block(
 *   id = "gv_fanatics_plus_checkout_integrants_selection",
 *   admin_label = @Translation("Integrants selection"),
 *   category = @Translation("GV Fanatics / Plus")
 * )
 */
class IntegrantsSelectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

	protected $integrant;
	protected $user;
	protected $image;
	protected $session;
	protected $cart;
	protected $checkoutOrderManager;
	protected $translationService;

	/**
	 * The current route match.
	 *
	 * @var \Drupal\Core\Routing\RouteMatchInterface
	 */
	protected $routeMatch;

	public function __construct(array $configuration, $plugin_id, $plugin_definition, $integrant, $session, $user, $image, $cart, $checkoutOrderManager, RouteMatchInterface $route_match, $translationService) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);

		$this -> session = $session;
		$this -> integrant = $integrant;
		$this -> user = $user;
		$this -> image = $image;
		$this -> cart = $cart;
		$this -> checkoutOrderManager = $checkoutOrderManager;
		$this -> routeMatch = $route_match;
		$this -> translationService = $translationService;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static(
			$configuration, 
			$plugin_id, 
			$plugin_definition, 
			$container -> get('gv_fanatics_plus_checkout.integrant'), 
			$container -> get('gv_fplus.session'), 
			$container -> get('gv_fplus_auth.user'), 
			$container -> get('gv_fplus_auth.image'), 
			$container -> get('gv_fanatics_plus_cart.cart'), 
			$container -> get('gv_fanatics_plus_checkout.checkout_order_manager'), 
			$container -> get('current_route_match'),
			$container -> get('gv_fanatics_plus_translation.interface_translation')
		);
	}

	/**
	 * Builds the integrants selection block.
	 *
	 * @return array
	 *   A render array.
	 */
	public function build() {
		$integrants = [];
		$cart = $this->cart->getCurrentDetail()->Booking;
		
		$activeIntegrantClientID = $this->session->getActiveIntegrantClientID();
		$activeIntegrant = NULL;
		$owner = NULL;
		$ownerProfile =  $this->user->getProfileByID($this->session->getIDUser());
		$ownerProfileImage = $this->image->getBySessionID($this->session->getIdentifier());
				
		$owner = $ownerProfile;
		$owner->ImageBase64 = $ownerProfileImage->ImageBase64;

		if (!isset($activeIntegrantClientID)) { // active user is the owner
			$owner->Active = TRUE;
		} else {
			$owner->Active = FALSE;
		}
		
		$switchOwnerUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION], ['query' => ['switch-owner' => '1']]);
		$owner->SwitchUrl = $switchOwnerUrl;
			
		$integrants = $this->integrant->listMembers($cart->IDClient)->List;
		foreach ($integrants as $index => $integrant) {
			if ($integrant->IntegrantID == $activeIntegrantClientID) {
				$integrants[$index]->Active = TRUE;
			} else {
				$integrants[$index]->Active = FALSE;
			}
			
			$switchIntegrantUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($integrant->IntegrantID)]]);
			$integrants[$index]->SwitchUrl = $switchIntegrantUrl;
		}
		
		$addNewIntegrantUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA], ['query' => ['register-new-integrant' => '1']]);
		return ['#attached' => ['library' => ['gv_fanatics_plus_checkout/integrants_selection'], ], '#theme' => 'gv_fanatics_plus_checkout_integrants_selection', '#integrants' => $integrants, '#owner' => $owner, '#add_new_integrant_url' => $addNewIntegrantUrl];
	}

	/**
	* {@inheritdoc}
	*/
	public function getCacheMaxAge() {
		return 0;
	}

}
