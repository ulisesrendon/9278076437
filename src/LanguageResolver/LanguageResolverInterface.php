<?php

namespace Drupal\gv_fplus\LanguageResolver;

/**
 * Interfaz para la entidad responsable por retornar el idioma de Doblemente en uso.
 */
interface LanguageResolverInterface {	
	public function resolve($reset = FALSE);
	public function isActive($langCode);
}

?>
