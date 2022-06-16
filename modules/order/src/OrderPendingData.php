<?php

namespace Drupal\gv_fanatics_plus_order;

use Drupal\Core\Url;

/**
 * Modelos representativo de datos pendientes de expedientes de Doblemente
 */
class PendingData {
	public $Identifier;
	public $Locator;
	public $PendingSignature;
	public $PendingPayment;
	public $OverduePayment;
	public $PendingShippingMethod;
	public $PaymentManagement;
	
	/** @var ServicePendingData[] **/
	public $Services;
	
	public $orderService;
	
	public function getNumberPendingActions() {
		$counter = 0;
		
		if ($this->PendingSignature) {
			++$counter;
		}
		
		if ($this->OverduePayment) {
			++$counter;
		}
		
		foreach ($this->Services as $service) {
			if ($service->RechargeAvailable) {
				++$counter;
			}
			
			if ($service->PendingPhoto) {
				++$counter;
			}
		}
		
		$order = $this->orderService::getFromID($this->Identifier, FALSE, TRUE);
		if ($order->hasPendingShippingMethod()) {
			++$counter;
		}
		
		return $counter;
	}
}

class ServicePendingData {
	public $Identifier;
	public $Description;
	public $RechargeAvailable;
	public $PendingPhoto;
}

class OrderPendingData {	
	private $apiClient;
	private $session;
	private $orderService;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
		$this->orderService = \Drupal::service('gv_fanatics_plus_order.order');
	}
	
	public function getFromOrderID($orderID) {
		$pendingActions = $this->apiClient->booking()->getPendingData($orderID);
		$mapper = new \JsonMapper();
		$mapper->bStrictNullTypes = false;
		$mappedPendingActions = $mapper->map($pendingActions, new PendingData());
		
		$mappedPendingActions->orderService = $this->orderService;
		
		return $mappedPendingActions;
	}
}

?>
