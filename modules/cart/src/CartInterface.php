<?php

namespace Drupal\gv_fanatics_plus_cart;

/**
 * Interfaz para el servicio de carrito de usuario
 */
interface CartInterface {
	
	/**
	 * Retorna el carrito actual
	 */
	public function getCurrent();
	
	/**
	 * Retorna el carrito actual con más información
	 * 
	 * @param $loadClients TRUE para retornar la información de clientes asociados al servicio, FALSE en caso contrario
	 */
	public function getCurrentDetail($loadClients = FALSE);
		
	/*
	public function getCancelationFees($sessionID, $bookingID);
	
	public function cancel($sessionID, $bookingID);
	*/
	
	/**
	 * Añade un servicio al carrito del usuario
	 * 
	 * @param $searchID Identificador de búsqueda de Doblemente
	 * @param $productBookingCode Código de producto de Doblemente
	 * @param $quantity cantidad del producto a añadir, por omisión es 1
	 */
	public function addBookingService($searchID, $productBookingCode, $quantity = 1);

	/**
	 * Elimina un servicio del carrito
	 * 
	 * @param $bookingServiceID identificador del servicio a eliminar
	 */
	public function removeBookingService($bookingServiceID);
	
	/**
	 * Confirma el expediente correspondiente al pedido actual
	 * 
	 * @param $paymentMethodID Identificador del método de pago
	 * @param $clientReference Referencia del cliente
	 * @param $bookingOfficeID Identificador de taquilla del expediente
	 * @param $relatedBookingLocator Identificador del localizador de referencia (MemberGetMember)
	 * @param $internalNotes Notas internas del expediente
	 */
	public function confirmBooking($paymentMethodID, $clientReference = NULL, $bookingOfficeID = NULL, $relatedBookingLocator = NULL, $internalNotes = NULL);
}

?>