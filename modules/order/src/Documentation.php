<?php

namespace Drupal\gv_fanatics_plus_order;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Modelos representativos de la documentación de expediente
 */
class DocumentationResult {
	public $Identifier;
	public $URLUpload;
}

class Documentation {	
	private $apiClient;
	private $session;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function getURLUpload($IDDocument) {
		$result = $this->apiClient->documentation()->getURLUploadDocument($this->session->getIdentifier(), $IDDocument);
		$mapper = new \JsonMapper();
		$mapper->bStrictNullTypes = false;
		$documentationResult = $mapper->map($result, new DocumentationResult());
		return $documentationResult;
	}
	
	public function uploadDocuments($documentData) {
		return $this->apiClient->documentation()->uploadDocument($this->session->getIdentifier(), $documentData);
	}
}

?>
