<?php

namespace Drupal\gv_fplus_auth;

/**
 * Modelo representativo de la imágenes de perfil de usuario de Doblemente
 */
class Image {
	private $apiClient;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
	}
	
	public function upload($sessionID, $imageBase64, $fileExtension = '.jpg', $name = NULL, $integrantID = NULL) {
		return $this->apiClient->images()->upload($sessionID, $imageBase64, $fileExtension, $name, $integrantID);
	}
	
	public function getBySessionID($sessionID) {
		try {
			$response = $this->apiClient->images()->getBySessionID($sessionID);
		} catch(\Exception $e) {
			return NULL;
		}
		
		return $response;
	}
	
	public function userHasImage($sessionID) {
		return $this->apiClient->images()->userHasImage($sessionID);
	}
}

?>
