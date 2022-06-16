<?php

namespace Drupal\gv_fanatics_plus_cart\Plugin\Block;

use Drupal\gv_fanatics_plus_cart\CartInterface;
use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\gv_fanatics_plus_cart\Form\CartBlockForm;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Expone el bloque del carrito para el proceso de checkout
 *
 * @Block(
 *   id = "gv_fanatics_plus_cart_block",
 *   admin_label = @Translation("Cart block	"),
 *   category = @Translation("GV Fanatics / Plus")
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

	protected $cart;
	protected $user;
	protected $routeMatch;
	
	protected $checkoutOrderManager;
	
	protected $cartInstance;

	/**
	 * Constructs a new CartBlock.
	 *
	 * @param array $configuration
	 *   A configuration array containing information about the plugin instance.
	 * @param string $plugin_id
	 *   The plugin ID for the plugin instance.
	 * @param mixed $plugin_definition
	 *   The plugin implementation definition.
	 * @param \Drupal\gv_fanatics_plus_cart\CartInterface $cart
	 *   The cart service.
	 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
	 *   The current route match.
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, CartInterface $cart, $user, RouteMatchInterface $route_match, $checkoutOrderManager) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);

		$this -> cart = $cart;
		$this -> routeMatch = $route_match;
		$this -> user = $user;
		$this->checkoutOrderManager = $checkoutOrderManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static($configuration, $plugin_id, $plugin_definition, $container -> get('gv_fanatics_plus_cart.cart'), $container -> get('gv_fplus_auth.user'),$container -> get('current_route_match'), $container->get('gv_fanatics_plus_checkout.checkout_order_manager'));
	}

	/**
	 * Builds the checkout progress block.
	 *
	 * @return array
	 *   A render array.
	 */
	public function build() {
		$form = \Drupal::formBuilder()->getForm('Drupal\gv_fanatics_plus_cart\Form\CartBlockForm');
		return ['form' => $form];
	}
	
	public function getForm() {
		return \Drupal::formBuilder()->getForm(CartBlockForm::class);
	}

	/**
   * Indicates whether the block should be shown.
   *
   * Blocks with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see self::access()
   */
  	protected function blockAccess(AccountInterface $account) {
  		$currentCheckoutStep = $this->checkoutOrderManager->getCurrentStepId();
		if ($currentCheckoutStep == CheckoutOrderSteps::PROFILE_DATA 
		|| $currentCheckoutStep == CheckoutOrderSteps::POST_PAYMENT 
		|| $currentCheckoutStep == CheckoutOrderSteps::COMPLETE) {
			return AccessResult::forbidden();
		}
		
    	// By default, the block is visible.
    	return AccessResult::allowed();
  	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheMaxAge() {
		return 0;
	}
}
