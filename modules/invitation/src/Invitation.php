<?php

namespace Drupal\gv_fanatics_plus_invitation;

use Drupal\Core\Url;

class InvitationUsage {
	public $BookingLocator;
	public $IDBooking;
}

class InvitationModel {
	public $Locator;
	public $Description;
	public $Integrants;
	public $ConsumedInvitation;
	public $AvailableInvitation;
	public $IDExpediente;
	public $IDService;
	
	/** @var InvitationUsage[] */
	public $InvitationUsage;
	
	public function getRedemptionURL() {
		return Url::fromRoute('gv_fanatics_plus_invitation.use_invitation', ['orderID' => $this->IDExpediente, 'serviceID' => $this->IDService]);
	}
	
	public function getRedemptionConfirmationModalURL() {
		return Url::fromRoute('gv_fanatics_plus_invitation.use_invitation_modal', ['orderID' => $this->IDExpediente, 'serviceID' => $this->IDService]);
	}
}

class Invitation {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	/**
	 * Retorna todas las invitaciones para un usuario.
	 * 
	 * @param $sessionID ID de sesión
	 * @param $userID ID de usuario
	 */
	public function getAll($sessionID, $userID = NULL) {
		$invitations =  $this->apiClient->invitations()->getAll($sessionID, $userID)->List;
		$mappedInvitations = array_map(function($invitation) {
			$mapper = new \JsonMapper();
			$mapper->bStrictNullTypes = false;
			$invitationModel = $mapper->map($invitation, new InvitationModel());
			return $invitationModel;
		}, $invitations);
		
		return $mappedInvitations;
	}
	
	/**
	 * Retorna todas las invitaciones para un servicio de un expediente
	 * 
	 * @param $orderID identificador de expediente
	 * @param $orderServiceID identificador de servicio de expediente
	 */
	public function checkForOrderID($orderID, $orderServiceID) {
		return $this->apiClient->invitations()->checkForOrderID($orderID, $orderServiceID);
	}
	
	/**
	 * Redime una invitación
	 * 
	 * @param $sessionID identificador de sesión
	 * @param $orderID identificador del expediente
	 * @param $orderServiceID identificador del servicio de expediente
	 */
	public function redeem($sessionID, $orderID, $orderServiceID) {
		return $this->apiClient->invitations()->redeem($sessionID, $orderID, $orderServiceID);
	}
}

?>
