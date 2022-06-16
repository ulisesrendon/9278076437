<?php

namespace Drupal\gv_fplus\LanguageResolver;

/**
 * Entidad responsable por retornar el idioma de Doblemente en uso
 */
class LanguageResolver implements LanguageResolverInterface
{
	private $activeLanguage;
	private $languageCodeMap;
	
	public function __construct() {		
		$this->activeLanguage = NULL;
	}
	
	/**
	 * Método responsable por retornar el idioma de Doblemente en uso.
	 * Este método mapea el idioma activo de Drupal al idioma del BackEnd de Doblemente.
	 * @param $reset TRUE para refrescar la caché interna de idioma activo, FALSE para hacer uso de la caché interna.
	 */
	public function resolve($reset = FALSE) {
		if (!$reset && $this->activeLanguage != NULL) {
			return $this->activeLanguage;
		}
		
		if ($this->languageCodeMap == NULL) {
			$this->languageCodeMap = [
				'es' => new Models\Language(1, "Castellano", "CAS", 'es'),
				'ca' => new Models\Language(54, "Català", "CAT", 'ca'),
				'en' => new Models\Language(21, "Inglés", "EN", 'en'),
				'fr' => new Models\Language(45, "Français", "FRA", 'fr')
			];	
		}
		
		$currentLangCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		$currentLanguage = $this->languageCodeMap[$currentLangCode];
		if (!isset($currentLanguage)) {
			$currentLanguage = $this->languageCodeMap['en'];
		}
		$this->activeLanguage = $currentLanguage;
		
		return $currentLanguage;
	}
	
	/**
	 * Método auxiliar que mapea ID's de idioma de Doblemente al código de idioma de Drupal.
	 */
	public static function getLangCodeFromID($languageID) {
		$languageMap = [
			1 => 'es',
			54 => 'ca',
			21 => 'en',
			45 => 'fr'
		];
		
		return $languageMap[$languageID];
	}

	public function isActive($langCode) {
		$currentLanguage = $this->resolve();
		return ($currentLanguage->isoCode() == $langCode);
	}
}

?>
