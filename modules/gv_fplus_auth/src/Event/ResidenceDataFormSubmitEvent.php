<?php

namespace Drupal\gv_fplus_auth\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Define 
 */
class ResidenceDataFormSubmitEvent extends Event {

	protected $isCreatingIntegrant;
	protected $isManagingIntegrant;
	protected $IDIntegrant;

	public function __construct($isCreatingIntegrant, $isManagingIntegrant, $IDIntegrant) {
		$this->isCreatingIntegrant = $isCreatingIntegrant;
		$this->isManagingIntegrant = $isManagingIntegrant;
		$this->IDIntegrant = $IDIntegrant;
	}

	public function isCreatingIntegrant() {
		return $this->isCreatingIntegrant;
	}

	public function isManagingIntegrant() {
		return $this->isManagingIntegrant;
	}
	
	public function getIDIntegrant() {
		return $this->IDIntegrant;
	}

}

?>
