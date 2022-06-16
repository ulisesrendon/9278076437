<?php

namespace Drupal\gv_fplus\CurrencyResolver\Models;

/**
 * Modelo de la entidad de representativa de la moneda del BackEnd de Doblemente
 * Más información: http://pruebasapi.grandvalira.com/swagger/ui/index#!/Core/WebDataCore_Currency
 */
class Currency {
	private $id;
	private $code;
	private $label;
	private $symbol;
	private $numDecimals;
	
	public function __construct($id, $code, $label, $symbol, $numDecimals)
	{
		$this->id = $id;
		$this->code = $code;
		$this->label = $label;
		$this->symbol = $symbol;
		$this->numDecimals = $numDecimals;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function code() {
		return $this->code;
	}
	
	public function label() {
		return $this->label;
	}
	
	public function symbol() {
		return $this->symbol;
	}
	
	public function numDecimals() {
		return $this->numDecimals;
	}
}

?>
