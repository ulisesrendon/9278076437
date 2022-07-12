<?php

namespace Drupal\gv_fplus\ChannelResolver\Models;

/**
 * Modelo del canal de venta representado en Drupal como término de taxonomía: 
 * https://fanatics.grandvalira.com/admin/structure/taxonomy/manage/fplus_canales_de_venta/overview
 */
class SalesChannel {
	private $id;
	private $name;
	private $active;
	private $dbm_id;
	private $IDServiceType;
	private $startSearchDate;
	private $endSearchDate;
	private $salesforceServiceTypeID;
	private $salesforceAccount;
	private $testEnvironment;
	private $hideProductSearchFilter;
	private $baseUrl;
	private $apiUsername;
	private $apiPassword;
	private $apiLicense;
	private $gtmCode;
	private $isMemberGetMemberActive;
	
	public function __construct($id, $name, $dbm_id, $active, $IDServiceType, $startSearchDate, $endSearchDate, $salesforceServiceTypeID, $salesforceAccount, $hideProductSearchFilter = FALSE, $testEnvironment = FALSE, $baseUrl, $apiUsername, $apiPassword, $apiLicense, $gtmCode, $isMemberGetMemberActive = FALSE)
	{
		$this->id = $id;
		$this->name = $name;
		$this->dbm_id = $dbm_id;
		$this->active = $active;
		$this->IDServiceType = $IDServiceType;
		$this->startSearchDate = $startSearchDate;
		$this->endSearchDate = $endSearchDate;
		$this->salesforceServiceTypeID = $salesforceServiceTypeID;
		$this->salesforceAccount = $salesforceAccount;
		$this->baseUrl = $baseUrl;
		$this->apiUsername = $apiUsername;
		$this->apiPassword = $apiPassword;
		$this->apiLicense = $apiLicense;
		$this->hideProductSearchFilter = $hideProductSearchFilter;
		$this->testEnvironment = $testEnvironment;
		$this->gtmCode = $gtmCode;
		$this->isMemberGetMemberActive = $isMemberGetMemberActive;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function active() {
		return $this->active;
	}
	
	public function dbm_id() {
		return $this->dbm_id;
	}
	
	public function getIDServiceType() {
		return $this->IDServiceType;
	}
	
	public function getStartSearchDate() {
		return $this->startSearchDate;
	}
	
	public function getEndSearchDate() {
		return $this->endSearchDate;
	}
	
	public function getSalesforceServiceTypeID() {
		return $this->salesforceServiceTypeID;
	}
	
	public function getSalesforceAccount() {
		return $this->salesforceAccount;
	}
	
	public function hideProductSearchFilter() {
		return $this->hideProductSearchFilter;
	}
	
	public function testEnvironment() {
		return $this->testEnvironment;
	}
	
	public function getBaseURL() {
		return $this->baseUrl;
	}
	
	public function getApiUsername() {
		return $this->apiUsername;
	}
	
	public function getApiPassword() {
		return $this->apiPassword;
	}
	
	public function getApiLicense() {
		return $this->apiLicense;
	}
	
	public function isFanatics() {
		return ($this->dbm_id == 2);
	}
	
	public function isPlus() {
		return ($this->dbm_id == 3);
	}
	
	public function isTemporadaOA() {
		return ($this->dbm_id == 25);
	}

	public function isPal() {
		return ($this->dbm_id == 41);
	}
	
	public function getGTMCode() {
		return $this->gtmCode;
	}
	
	public function isMemberGetMemberActive() {
		return $this->isMemberGetMemberActive;
	}
}

?>
