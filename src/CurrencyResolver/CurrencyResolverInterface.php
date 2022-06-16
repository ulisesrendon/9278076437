<?php

namespace Drupal\gv_fplus\CurrencyResolver;

/**
 * Interfaz para la entidad responsable por retornar la moneda en uso.
 */
interface CurrencyResolverInterface {	
	public function resolve($reset = FALSE);
	public function isActive($currencyID);
}

?>
