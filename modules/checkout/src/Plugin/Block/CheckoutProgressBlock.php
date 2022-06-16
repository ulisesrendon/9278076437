<?php

namespace Drupal\gv_fanatics_plus_checkout\Plugin\Block;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Url;


/**
 * Implementa el bloque de indicación progreso del proceso de checkout.
 *
 * @Block(
 *   id = "gv_fanatics_plus_checkout_progress",
 *   admin_label = @Translation("Checkout progress"),
 *   category = @Translation("GV Fanatics / Plus")
 * )
 */
class CheckoutProgressBlock extends BlockBase implements ContainerFactoryPluginInterface {

	/**
	 * The checkout order manager.
	 *
	 * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
	 */
	protected $checkoutOrderManager;
	protected $postPaymentOrderManager;

	/**
	 * The current route match.
	 *
	 * @var \Drupal\Core\Routing\RouteMatchInterface
	 */
	protected $routeMatch;

	/**
	 * Constructs a new CheckoutProgressBlock.
	 *
	 * @param array $configuration
	 *   A configuration array containing information about the plugin instance.
	 * @param string $plugin_id
	 *   The plugin ID for the plugin instance.
	 * @param mixed $plugin_definition
	 *   The plugin implementation definition.
	 * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
	 *   The checkout order manager.
	 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
	 *   The current route match.
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutOrderManagerInterface $checkout_order_manager, $post_payment_order_manager, RouteMatchInterface $route_match) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);

		$this -> checkoutOrderManager = $checkout_order_manager;
		$this -> postPaymentOrderManager = $post_payment_order_manager;
		$this -> routeMatch = $route_match;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static($configuration, $plugin_id, $plugin_definition, $container -> get('gv_fanatics_plus_checkout.checkout_order_manager'), $container -> get('gv_fanatics_plus_checkout.post_payment_order_manager'), $container -> get('current_route_match'));
	}

	/**
	 * Builds the checkout progress block.
	 *
	 * @return array
	 *   A render array.
	 */
	public function build() {
		// Prepare the steps as expected by the template.
		$steps = [];
		$visible_steps = $this -> checkoutOrderManager -> getVisibleSteps();
		$requested_step_id = $this -> routeMatch -> getParameter('step');
		$current_step_id = $this -> checkoutOrderManager -> getCheckoutStepId($requested_step_id);
		$current_step_index = array_search($current_step_id, array_keys($visible_steps));

		$isPostPaymentActive = $this->postPaymentOrderManager->isPostPaymentActive();

		$index = 0;
		foreach ($visible_steps as $step_id => $step_definition) {
			if ($index < $current_step_index) {
				$position = 'previous';
			} elseif (($isPostPaymentActive && $step_id == CheckoutOrderSteps::POST_PAYMENT)) {
				$position = 'current';
			} elseif (($isPostPaymentActive && $step_id != CheckoutOrderSteps::POST_PAYMENT)) {
				$position = 'previous';
			} elseif ((!$isPostPaymentActive && $index == $current_step_index)) {
				$position = 'current';
			}
			else {
				$position = 'next';
			}
			$index++;
			// Hide hidden steps until they are reached.
			if (!empty($step_definition['hidden']) && $position != 'current') {
				continue;
			}
			
			$newStep = [
				'id' => $step_id, 
				'label' => $step_definition['label'], 
				'position' => $position,
				'route' => NULL
			];
			
			if (!$isPostPaymentActive && $position == 'previous') {
				$newStep['route'] = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => $step_id]);
			}
			
			$steps[] = $newStep;
		}

		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_checkout/checkout_progress'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_checkout_progress', 
			'#steps' => $steps
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheMaxAge() {
		return 0;
	}

}
