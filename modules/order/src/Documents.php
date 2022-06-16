<?php

namespace Drupal\gv_fanatics_plus_order;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Modelos representativos de documentos de Doblemente
 */

class Document {
	use StringTranslationTrait;
	
	public $Name;
	public $DocumentToBase64;
	public $Format;
	public $Exists;
	public $IDDocumentType;
	public $SignatureProcessCompleted;
	
	const TYPE_ID_TO_LABEL = [
		1 => 'confirmation',
		2 => 'cancellation',
		3 => 'proforma',
		9 => 'invoice',
		7 => 'signed contract',
		8 => 'signed certificate'
	];
	
	public function getDownloadUrl($orderID, $documentType = NULL) {
		return Url::fromRoute('gv_fanatics_plus_order.document_download', ['orderID' => $orderID, 'documentType' => $this->IDDocumentType]);
	}
	
	public function getIDDocumentTypeName() {

	    if ($this->SignatureProcessCompleted === NULL || $this->SignatureProcessCompleted === TRUE) {
			if (isset(Document::TYPE_ID_TO_LABEL[$this->IDDocumentType])) {
				return $this->t('See @document_label', ['@document_label' => $this->t(Document::TYPE_ID_TO_LABEL[$this->IDDocumentType])], []);
			} else {
				return $this->t('See', [], []);
			}
		} else if ($this->SignatureProcessCompleted === FALSE) {
			$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
			if ($this->IDDocumentType == 7) {
				return $translationService->translate('DOCUMENTS.SIGNED_CONTRACT_SIGNATURE_INCOMPLETE');
			} else if ($this->IDDocumentType == 8) {
				return $translationService->translate('DOCUMENTS.SIGNED_CERTIFICATE_SIGNATURE_INCOMPLETE');
			} else if (isset(Document::TYPE_ID_TO_LABEL[$this->IDDocumentType])) {
				return $this->t('See @document_label', ['@document_label' => $this->t(Document::TYPE_ID_TO_LABEL[$this->IDDocumentType])], []);
			} else {
				return $this->t('See', [], []);
			}
		}
	}
}

class Documents {	
	private $apiClient;
	private $session;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function getFromOrderID($orderID, $documentTypeID = NULL, $clientID = NULL) {
	    
	    $documents = $this->apiClient->documents()->getAll($orderID, $documentTypeID, $clientID)->List;
	    
		if (!isset($documents) || count($documents) <= 0) {
			return [];
		}
		
		$mappedDocuments = array_map(function($document) {
			$mapper = new \JsonMapper();
			$mapper->bStrictNullTypes = false;
			$order = $mapper->map($document, new Document());
			return $order;
		}, $documents);
		
		return $mappedDocuments;
	}
	
	public function getAllTypes() {
		return $this->apiClient->documents()->getTypes();
	}
}

?>
