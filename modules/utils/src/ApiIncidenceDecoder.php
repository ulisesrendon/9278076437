<?php

namespace Drupal\gv_fanatics_plus_utils;

/**
 * Decodificador de error de BackEnd de API
 */
class ApiIncidenceDecoder {
	
	private $apiClient;
	private $languageResolver;
	private $incidenceMap;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->languageResolver = \Drupal::service('gv_fplus.language_resolver');
		$this->incidenceMap = NULL;
	}
	
	public function decode($errorCode, $languageID = NULL) {		
		if (!isset($this->incidenceMap)) {
			$allIncidences = $this->apiClient->incidences()->getAll()->List;
			$this->_buildIncidenceMap($allIncidences);	
		}
		
		$incidence = $this->incidenceMap[$errorCode];
		$translations = $incidence->List;
		if (!isset($languageID)) {
			$languageID = $this->languageResolver->resolve()->id();
		}
		$translatedMsg = array_pop(array_reverse(array_filter($translations, function ($translation) use ($languageID) { return $translation->LanguageID == $languageID; })));
		if (!isset($translatedMsg)) {
			return NULL;
		}
				
		return $translatedMsg->Description;
	}
	
	public function decodeAll(Array $errorCodes) {
		if (!isset($errorCodes) || count($errorCodes) < 0) {
			return [];
		}
		
		$results = [];
		foreach ($errorCodes as $errorCode) {
			$msg = $this->decode($errorCode->ErrorCode);
			$results[] = $msg;
		}

		return $results;
	}
	
	private function _buildIncidenceMap($incidences) {
		$incidenceMap = [];
		foreach ($incidences as $incidence) {
			$incidenceMap[$incidence->CodeIncidence] = $incidence;
		}
		
		$this->incidenceMap = $incidenceMap;
	}
}

?>
