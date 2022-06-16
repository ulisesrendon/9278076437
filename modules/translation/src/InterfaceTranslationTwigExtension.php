<?php

namespace Drupal\gv_fanatics_plus_translation;

use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Registra un extensión de twig que hace uso del servicio de InterfaceTranslation (|gv_t)
 */
class InterfaceTranslationTwigExtension extends Twig_Extension {
	
	private $interfaceTranslationService;
	
	public function __construct() {
		$this->interfaceTranslationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
	}

	public function getName() {
		return 'gv_fanatics_plus_translation.interface_translation_twig_extension';
	}

	public function getFilters() {
		return [new Twig_SimpleFilter('gv_t', [$this, 'translate']), ];
	}
	
	/**
	 * Función para traducir una cadena / constante
	 * 
	 * @param $text cadena a traducir
	 * @param $variableData datos comodín para reemplazar patrones en la cadena
	 * @param $context contexto de la cadena a registrar
	 * @param $targetLanguageCode código de idioma para generar la traducción, por omisión corresponde al idioma del sitio
	 */
	public function translate($text, $variableData = [], $context = NULL, $targetLanguageCode = NULL) {
		return $this->interfaceTranslationService->translate($text, $variableData, $context, $targetLanguageCode);
	}
}
?>
