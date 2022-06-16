<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Controller\ControllerBase;

/**
 * Controlador para ejecutar pruebas, no tiene cualquier funcionalidad.
 */
class TestController extends ControllerBase {

	private $apiClient;
	private $session;
	private $metricsCollector;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$metricsCollector = $container->get('gv_fanatics_plus_metrics_collector.metrics_collector');
		$cart = $container->get('gv_fanatics_plus_cart.cart');
		return new static($apiClient, $session, $cart, $metricsCollector);
	}
	
	public function __construct($apiClient, $session, $cart, $metricsCollector) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->cart = $cart;
		$this->metricsCollector = $metricsCollector;
	}

	public function test() {
		/*$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		$translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_RETRY_BTN_LABEL');
		*/
		
		return [];
	}

}

?>
