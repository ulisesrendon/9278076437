<?php

namespace Drupal\gv_fplus_auth;

/**
 * Modelo representativo de la entidad de usuario de Doblemente
 */
class User {
	private $apiClient;
	private $channelResolver;
	private $session;
	
	/**
	 * Utiliza una caché en memoria para evitar hacer la misma solicitud más que una vez 
	 * en el ciclo de vida de una página
	 */
	private $memoizationCache;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function exists($email) {
		$response = $this->apiClient->users()->userExists($email);
		return $response->ExistsUser;
	}
	
	public function isVerified($email) {
		$userProfile = $this->getProfile($email);
		if ($userProfile->Verified) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function isProfileDataComplete($email) {
		$userProfile = $this->getProfile($email);
		if ($userProfile->DataCompleted) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function isProfileDataCompleteByClientID($clientID) {
		//$userProfile = $this->apiClient->users()->getUserProfileByClientID($clientID);
		$userProfile = $this->getUserProfileByClientID($clientID);
		if ($userProfile->DataCompleted) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function isDefaulting($email) {
		//$userProfile = $this->apiClient->users()->getUserProfile($email);
		$userProfile = $this->getProfile($email);
		if ($userProfile->SkiData->HasOverduePayments) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function sendActivation($email, $languageID, $salesChannelID) {
		//$testEnvironment = $this->channelResolver->resolve()->testEnvironment();
		return $this->apiClient->users()->sendActivation($email, $languageID, $salesChannelID);
	}
	
	public function rememberPassword($email, $languageID, $salesChannelID) {
		//$testEnvironment = $this->channelResolver->resolve()->testEnvironment();
		return $this->apiClient->users()->rememberPassword($email, $languageID, $salesChannelID);
	}
	
	public function resetPassword($email, $newPassword) {
		return $this->apiClient->users()->resetPassword($email, $newPassword);
	}
	
	public function getProfile($email, $returnImage = FALSE, $returnPhotoStatus = FALSE, $memoize = TRUE) {
		$identifier = $this->session->getIdentifier();
		$key = 'getProfile_' . $email . '_' . $returnImage . '_' . $returnPhotoStatus . '_' . $identifier;
		
		if (isset($this->memoizationCache[$key]) && $memoize == TRUE) {
			return $this->memoizationCache[$key];
		}
		
		$response = $this->apiClient->users()->getUserProfile($email, $returnImage, $returnPhotoStatus, $identifier);
		if ($memoize == TRUE) {
			$this->memoizationCache[$key] = $response;
		}
		
		return $response;
	}
	
	public function getProfileByID($userID, $returnImages = FALSE, $returnPhotoStatus = FALSE, $memoize = TRUE) {
		$identifier = $this->session->getIdentifier();
		$key = 'getProfileByID' . $userID . '_' . $returnImages . '_' . $returnPhotoStatus . '_' . $identifier;
		
		if (isset($this->memoizationCache[$key]) && $memoize == TRUE) {
			return $this->memoizationCache[$key];
		}
		
		$response = $this->apiClient->users()->getUserProfileByID($userID, $returnImages, $returnPhotoStatus, $identifier);
		if ($memoize == TRUE) {
			$this->memoizationCache[$key] = $response;
		}
		
		return $response;
	}
	
	public function getUserProfileByClientID($clientID, $returnImages = FALSE, $returnPhotoStatus = FALSE, $memoize = TRUE) {
		$identifier = $this->session->getIdentifier();
		$key = 'getUserProfileByClientID' . $clientID . '_' . $returnImages . '_' . $returnPhotoStatus . '_' . $identifier;
		
		if (isset($this->memoizationCache[$key]) && $memoize == TRUE) {
			return $this->memoizationCache[$key];
		}
		
		$response = $this->apiClient->users()->getUserProfileByClientID($clientID, $returnImages, $returnPhotoStatus, $identifier);
		if ($memoize == TRUE) {
			$this->memoizationCache[$key] = $response;
		}
		
		return $response;
	}
	
	public function basicRegister($sessionID, $email, $name, $password, $newsletter = NULL) {
		return $this->apiClient->users()->fanatics()->basicRegister($sessionID, $email, $name, $password, $newsletter);
	}
	
	public function updateReceiveInformation($userID, $email, $newsletter, $salesChannelID) {
		return $this->apiClient->users()->updateReceiveInformation($userID, $email, $newsletter, $salesChannelID);
	}
	
	public function decryptEmail($token) {
		return $this->apiClient->users()->getMailDecrypt($token);
	}
	
	public function getSeasons() {
		return $this->apiClient->users()->getSeasons($this->session->getIdentifier());
	}
	
	public function fanatics() {
		return new UserFanatics($this->apiClient);
	}
}

class UserFanatics {
	private $apiClient;
	
	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}
	
	public function update($sessionID = NULL, $email = NULL, $name = NULL, $surname1 = NULL, $surname2 = NULL, $cardID = NULL, $gender = NULL, $birthDate = NULL, $countryID = NULL, $postalCode = NULL, $city = NULL, $provinceID = NULL, $address = NULL, $addressNumber = NULL, $addressTypeID = NULL, $otherAdress = NULL, $phoneNumber = NULL, $newPassword = NULL, $receiveInfo = NULL, $census = NULL, $clubID = NULL, $clubCode = NULL, $province = NULL, $currentPassword = NULL) {
		return $this->apiClient->users()->fanatics()->update($sessionID, $email, $name, $surname1, $surname2, $cardID, $gender, $birthDate, $countryID, $postalCode, $city, $provinceID, $address, $addressNumber, $addressTypeID, $otherAdress, $phoneNumber, $newPassword, $receiveInfo, $census, $clubID, $clubCode, $province, $currentPassword);
	}
}

?>
