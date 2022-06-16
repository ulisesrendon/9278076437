<?php

namespace Drupal\gv_fplus\CurrencyResolver;

/**
 * Entidad responsable por retornar la moneda en uso.
 * Actualmente siempre retorna la moneda correspondiente al Euro, se ha implementado esta clase con vista a implementaciones futuras.
 */
class CurrencyResolver implements CurrencyResolverInterface
{
	private $activeCurrency;
	
	public function __construct() {
		$this->activeCurrency = new Models\Currency(1, "EUR", "Euro", "€", 2);
	}
	
	/**
	 * Método responsable por retornar la moneda activa.
	 */
	public function resolve($reset = FALSE) {
		return $this->activeCurrency;
	}

	public function isActive($currencyID) {
		return ($this->activeCurrency->id() == $currencyID);
	}
}

?>
