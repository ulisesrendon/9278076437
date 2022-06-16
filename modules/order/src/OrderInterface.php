<?php

namespace Drupal\gv_fanatics_plus_order;

/**
 * Interfaz para el modelo de expediente de Doblemente
 */
interface OrderInterface {
	
	public static function getFromID($orderID, $loadClients = FALSE);
	
}

?>
