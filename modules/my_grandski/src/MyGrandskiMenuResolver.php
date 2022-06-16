<?php

namespace Drupal\gv_fanatics_plus_my_grandski;

/**
 * Entidad responsable por retornar el identificador del menú MyGrandSki activo
 */
class MyGrandskiMenuResolver {
	
	private $channelResolver;
	
	public function __construct() {
		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
	}
	
	/**
	 * Retorna un identificador de menú MyGrandSki en función del canal de venta activo
	 */
	public function resolve() {
		$activeChannel = $this->channelResolver->resolve();
		if ($activeChannel == NULL) {
			return 'mygrandski';
		}
		
		if ($activeChannel->isPlus()) {
			return 'mygrandskiplus';
		}
		
		if ($activeChannel->isTemporadaOA()) {
			return 'mygrandskitemporada';
		}
		
		return 'mygrandski';
	}
	
}

?>
