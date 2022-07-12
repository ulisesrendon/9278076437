<?php

namespace Drupal\gv_fanatics_plus_translation;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Entidad que permite la traducción de cadenas basadas en constantes
 */
class InterfaceTranslation {
	use StringTranslationTrait;
	
	private $channelResolver;
	private $currentSalesChannel;
	
	private $currentTranslationContext;
	private $debugMode;
	
  	public function __construct() {
  		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$this->_resolveTranslationContext();
		$this->debugMode = TRUE;
  	}
	
	/**
	 * Función auxiliar para añadir un contexto a las cadenas registradas
	 */
	public function _resolveTranslationContext() {
		$this->currentSalesChannel = $this->channelResolver->resolve();
		
		if ($this->currentSalesChannel == NULL) {
			$this->currentTranslationContext = 'gv';
		} else if ($this->currentSalesChannel->isFanatics()) {
			$this->currentTranslationContext = 'gv_fanatics';
		} else if ($this->currentSalesChannel->isPlus()) {
			$this->currentTranslationContext = 'gv_plus';
		} else if ($this->currentSalesChannel->isTemporadaOA()) {
			$this->currentTranslationContext = 'oa_temporada';
		} else if ($this->currentSalesChannel->isPal()) {
			$this->currentTranslationContext = 'pal';
		} else {
			$this->currentTranslationContext = 'gv';
		}
		
		return $this->currentTranslationContext;
	}
	
	/**
	 * Función para traducir una cadena / constante
	 * 
	 * @param $string cadena a traducir
	 * @param $variableData datos comodín para reemplazar patrones en la cadena
	 * @param $context contexto de la cadena a registrar
	 * @param $targetLanguageCode código de idioma para generar la traducción, por omisión corresponde al idioma del sitio
	 */
	public function translate($string, $variableData = [], $context = NULL, $targetLanguageCode = NULL) {
		$baseContext = $this->currentTranslationContext;
		if ($context != NULL) {
			$baseContext = $context;
		}
		
		$baseTranslationParams = ['context' => $baseContext];
		if ($targetLanguageCode != NULL) {
			$baseTranslationParams['langcode'] = $targetLanguageCode;
		}

		if ($this->debugMode == TRUE) {
			$fanatics = $this->_string_replace($this->t($string, $variableData, ['context' => 'gv_fanatics'])->render(), $variableData);
			$plus = $this->_string_replace($this->t($string, $variableData, ['context' => 'gv_plus'])->render(), $variableData);
			$temporada = $this->_string_replace($this->t($string, $variableData, ['context' => 'oa_temporada'])->render(), $variableData);
		}
		
		return $this->_string_replace($this->t($string, $variableData, $baseTranslationParams), $variableData);
	}
	
	/**
	 * Función auxiliar para remplazar variables en una cadena
	 * 
	 * @param $string cadena base dónde reemplazar variables
	 * @param array $variableData datos a reemplazar en la cadena
	 */
	private function _string_replace($string, $variableData) {
		if (count($variableData) <= 0) {
			return $string;
		}
		
		return str_replace(array_keys($variableData), array_values($variableData), $string);
	}
}

?>
