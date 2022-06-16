<?php

namespace Drupal\gv_fplus\LanguageResolver\Models;

/**
 * Modelo de la entidad de representativa del idioma del BackEnd de Doblemente
 * Más información: http://pruebasapi.grandvalira.com/swagger/ui/index#!/Core/WebDataCore_Language
 */
class Language {
	private $id;
	private $name;
	private $acronym;
	private $isoCode;
	
	public function __construct($id, $name, $acronym, $isoCode)
	{
		$this->id = $id;
		$this->name = $name;
		$this->acronym = $acronym;
		$this->isoCode = $isoCode;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function name() {
		return $this->name;
	}
	
	public function acronym() {
		return $this->acronym;
	}
	
	public function isoCode() {
		return $this->isoCode;
	}
}

?>
