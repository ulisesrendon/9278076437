<?php

namespace Drupal\gv_fplus_auth;

/**
 * Modelo representativo de lógica de negocio de Doblemente.
 * Contiene apenas una fracción de la lógica de negocio.
 */
class FormBasicValidations {
	private $apiClient;
	private $location;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->location = \Drupal::service('gv_fplus_auth.location');
	}

	public function isValidCollectiveCode($sessionID, $collectiveTypeID, $collectiveCode) {
		return $this->apiClient->formBasicValidations()->isValidCollectiveCode($sessionID, $collectiveTypeID, $collectiveCode);
	}
	
	public function getCountriesThatRequirePostalCode() {
		return [5, 74, 173, 197];
	}
	
	public function isValidPostalCode($postalCode, $country) {
		$countriesRequirePostalCode = $this->getCountriesThatRequirePostalCode();
		if (!in_array($country, $countriesRequirePostalCode)) {
			return TRUE;
		}
		
		return $this->location->isValidPostalCode($postalCode, $country);
	}
	
	public function isValidPhoneNumber($phoneNumber, $countryISOCode) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
    		$numberProto = $phoneUtil->parse($phoneNumber, $countryISOCode);
    		$isValid = $phoneUtil->isValidNumber($numberProto);
			
			return $isValid;
		} catch (\libphonenumber\NumberParseException $e) {
    		return FALSE;
		}		
	}
	
	public function canEditCountry($sessionID) {
		return $this->apiClient->formBasicValidations()->canEditCountry($sessionID);
	}
	
	public function canEditBirthDate($sessionID) {
		return $this->apiClient->formBasicValidations()->canEditBirthDate($sessionID);
	}
	
	public function integrantCanEditCountry($integrantID) {
		return $this->apiClient->formBasicValidations()->integrantCanEditCountry($integrantID);
	}
	
	public function integrantCanEditBirthDate($integrantID) {
		return $this->apiClient->formBasicValidations()->integrantCanEditBirthDate($integrantID);
	}
	
	public function minimumAgeForBuying($sessionID) {
		return $this->apiClient->formBasicValidations()->minAgeForBuying($sessionID);
	}
}

?>
