<?php

namespace Drupal\gv_fplus_auth;

/**
 * Modelo representativo de ubicaciones de Doblemente
 */
class Location {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function getAll($postalCode, $country) {
		return $this->apiClient->locations()->getLocations($postalCode, $country);
	}
	
	public function getCountries() {
		return $this->apiClient->locations()->getCountries();
	}
	
	public function getCountryByID($countryID) {
		$response = $this->apiClient->locations()->getCountry($countryID);
		return (count($response->List) > 0 ? $response->List[0] : NULL);
	}
	
	public function isValidPostalCode($postalCode, $country) {
		return (count($this->getAll($postalCode, $country)->List) > 0);
	}
}

?>
