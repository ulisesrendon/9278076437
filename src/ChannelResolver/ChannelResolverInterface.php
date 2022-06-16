<?php

namespace Drupal\gv_fplus\ChannelResolver;

/**
 * Interfaz para la entidad responsable por retornar el canal de venta activo.
 */
interface ChannelResolverInterface {
	
	public function resolve($reset = FALSE);
	public function isActive($reset = FALSE);
}

?>
