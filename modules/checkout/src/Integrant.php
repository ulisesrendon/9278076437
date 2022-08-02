<?php

namespace Drupal\gv_fanatics_plus_checkout;

/**
 * Modelo representativo de integrantes de Doblemente
 */
class Integrant {
	private $apiClient;
	private $session;
	
	private $memoizationCache;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function create($sessionID, $integrantType, $email, $name, $surname, $surname2, $IDCard, $gender, $birthdate, $IDCountry, $postalCode, $city, $IDProvince, $provinceName, $address, $addressNumber, $IDAddressType, $addressMoreInfo, $phoneNumber, $telephoneNumber, $renewPass, $census, $IDClub, $clubIdentification, $IDCountryNationality = NULL, $IDCountryResidence = NULL, $PassportExpirationDate = NULL) {
		return $this->apiClient->integrants()->create($sessionID, $integrantType, $email, $name, $surname, $surname2, $IDCard, $gender, $birthdate, $IDCountry, $postalCode, $city, $IDProvince, $provinceName, $address, $addressNumber, $IDAddressType, $addressMoreInfo, $phoneNumber, $telephoneNumber, $renewPass, $census, $IDClub, $clubIdentification, $IDCountryNationality, $IDCountryResidence, $PassportExpirationDate);
	}
	
	public function update($sessionID, $integrantID, $integrantType, $email, $name, $surname, $surname2, $IDCard, $gender, $birthdate, $IDCountry, $postalCode, $city, $IDProvince, $provinceName, $address, $addressNumber, $IDAddressType, $addressMoreInfo, $phoneNumber, $telephoneNumber, $renewPass, $census, $IDClub, $clubIdentification, $IDCountryNationality = NULL, $IDCountryResidence = NULL, $PassportExpirationDate = NULL) {
		return $this->apiClient->integrants()->update($sessionID, $integrantID, $integrantType, $email, $name, $surname, $surname2, $IDCard, $gender, $birthdate, $IDCountry, $postalCode, $city, $IDProvince, $provinceName, $address, $addressNumber, $IDAddressType, $addressMoreInfo, $phoneNumber, $telephoneNumber, $renewPass, $census, $IDClub, $clubIdentification, $IDCountryNationality, $IDCountryResidence, $PassportExpirationDate);
	}
	
	public function delete($clientID) {
		return $this->apiClient->integrants()->remove($clientID);
	}
	
	public function listMembers($sessionID, $returnPhotoStatus = FALSE, $memoize = TRUE) {
		$key = 'listMembers_' . $sessionID . '_' . $returnPhotoStatus;
		if (isset($this->memoizationCache[$key]) && $memoize == TRUE) {
			return $this->memoizationCache[$key];
		}
		
		$response = $this->apiClient->integrants()->listMembers($sessionID, $returnPhotoStatus);
		if ($memoize == TRUE) {
			$this->memoizationCache[$key] = $response;
		}
		
		return $response;
	}
	
	public function listMember($clientID, $returnPhotoStatus = FALSE, $memoize = TRUE) {
		$identifier = $this->session->getIdentifier();
		$key = 'listMember_' . $clientID . '_' . $returnPhotoStatus . '_' . $identifier;
		if (isset($this->memoizationCache[$key]) && $memoize == TRUE) {
			return $this->memoizationCache[$key];
		}
		
		$response = $this->apiClient->integrants()->listMember($identifier, $clientID, $returnPhotoStatus);
		if ($memoize == TRUE) {
			$this->memoizationCache[$key] = $response;
		}
		
		return $response;
	}
}

?>
