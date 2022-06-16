<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controlador de documentos de expediente.
 * Deprecado.
 */
class OrderDocumentsController extends ControllerBase {

	private $apiClient;
	private $documents;
	private $order;
	private $session;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$order = $container->get('gv_fanatics_plus_order.order');
		$documents = $container->get('gv_fanatics_plus_order.documents');
		
		return new static($apiClient, $session, $order, $documents);
	}
	
	public function __construct($apiClient, $session, $order, $documents) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->order = $order;
		$this->documents = $documents;
	}

	public function orderDetail(RouteMatchInterface $route_match) {
		$orderID = $route_match->getParameter('orderID');
		$documentTypeID = $route_match->getParameter('documentTypeID');
		
		$documents = $this->documents->getFromOrderID($orderID, $documentTypeID);
		
		return ['#attached' => ['library' => ['gv_fanatics_plus_order/detail'], ], '#theme' => 'gv_fanatics_plus_order_detail', '#order' => $order];
	}

}

?>
