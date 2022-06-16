<?php

namespace Drupal\gv_fanatics_plus_logs;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Procesador del canal de mensajes de log de Fanatics
 */
class SalesChannelProcessor {
	const SESSION_STORAGE_PREFIX = 'gv_fplus_session';
	
    private $sessionManager;
	
    public function __construct() {
		$this->sessionManager = \Drupal::service('user.private_tempstore')->get(static::SESSION_STORAGE_PREFIX);
    }

    public function __invoke(array $record) {
    	$record['extra']['gv_fanatics_plus_session_id'] = $this->sessionManager->get('Identifier');
        $record['extra']['gv_fanatics_plus_user_id'] = $this->sessionManager->get('IDUser');
		$record['extra']['gv_fanatics_plus_user_email'] = $this->sessionManager->get('Email');
		$record['extra']['gv_fanatics_plus_integrant_client_id'] = $this->sessionManager->get('ActiveIntegrantClientID');
		
		$orderID = \Drupal::routeMatch()->getParameter('orderID');
		if (isset($orderID) && $orderID > 0) {
			$record['extra']['gv_fanatics_plus_order_id'] = $orderID;
		}
		
        return $record;
    }
}

?>
