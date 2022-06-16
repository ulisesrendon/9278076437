<?php

namespace Drupal\gv_fanatics_plus_contact;

use Drupal\Component\Utility\UrlHelper;

/**
 * Entidad responsable por construir los enlaces de para el formulario de contacto en contexto de soporte / incidencia
 */
class ContactPageLinkBuilder {
	const DEFAULT_CONTACT_PAGE_ALIAS = '/node/41';
	private $contactPageAlias;
	private $pathAliasManager;
	private $languageManager;
	
	public function __construct() {
		$this->pathAliasManager = \Drupal::service('path_alias.manager');
		$this->languageManager = \Drupal::languageManager();
		$this->contactPageAlias = ContactPageLinkBuilder::DEFAULT_CONTACT_PAGE_ALIAS;
	}
	
	/**
	 * Construye el enlace hacia la página de contacto
	 * 
	 * @param $orderID Identificador del expediente
	 * @param $incidence TRUE si se debe notificar de una incidencia, FALSE en caso contrario
	 */
	public function buildURL($orderID = NULL, $incidence = FALSE) {
		$queryParams = [];
		if ($orderID != NULL) {
			$queryParams['localizador'] = $orderID;
		}
		
		if ($incidence == TRUE) {
			$queryParams['categoria'] = 'CAT03';
			$queryParams['subtipo_fft'] = 'S11003';
		}
		
		$queryParams = UrlHelper::buildQuery($queryParams);
		$langCode = $this->languageManager->getCurrentLanguage()->getId();
		if ($langCode == 'es') {
			return $this->pathAliasManager->getAliasByPath($this->contactPageAlias, $langCode) . (strlen($queryParams) > 0 ? '?' : '') . $queryParams;
		}
		
		return $this->pathAliasManager->getAliasByPath('/' . $langCode . $this->contactPageAlias, $langCode) . (strlen($queryParams) > 0 ? '?' : '') . $queryParams;
	}
}
