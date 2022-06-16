<?php

namespace Drupal\gv_fanatics_plus_checkout;

use Drupal\gv_fanatics_plus_checkout\BookingOfficeOptions;

/**
 * Modelos representativos de taquillas del Doblemente.
 */

class BookingOfficeModel {
	public $Identifier;
	public $BookingOffice;
	
	public function __construct($identifier, $bookingOffice) {
		$this->Identifier = $identifier;
		$this->BookingOffice = $bookingOffice;
	}
}

class BookingOffice {
	private $apiClient;
	private $session;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function getBySessionID($sessionID = NULL) {
		if (!isset($sessionID)) {
			$sessionID = $this->session->getIdentifier();
		}
		
		return $this->apiClient->bookingHelper()->getBookingOffices($sessionID);
	}
	
	public function getBoxOfficeOptions($sessionID = NULL, $returnStatic = FALSE) {
		if (!isset($sessionID)) {
			$sessionID = $this->session->getIdentifier();
		}
		
		if (!$returnStatic) {
			$allBookingOffices = $this->apiClient->bookingHelper()->getBookingOffices($sessionID)->List;
		} else {
			$allBookingOffices = [
				new BookingOfficeModel(1, "Taquilla Grau Roig Informació"),
				new BookingOfficeModel(2, "Taquilla Pas de la Casa"),
				new BookingOfficeModel(3, "Taquilla Soldeu"),
				new BookingOfficeModel(4, "Illa Carlemany Punt Info"),
				new BookingOfficeModel(5, "Taquilla Funicamp"),
				new BookingOfficeModel(6, "Taquilla Tarter"),
				new BookingOfficeModel(8, "Pintor Rosales - Paseo Pintor Rosales, 32 28008 MADRID"),
				new BookingOfficeModel(9, "Taquilla Canillo"),
				new BookingOfficeModel(10, "Taquillas Ordino Arcalís"),
				new BookingOfficeModel(12, "Stand Grandvalira Resort, centre comercial l'Illa Carlemany"),
				new BookingOfficeModel(13, "25, 26 y 27 de Octubre a la Fira de Andorra")
			];
		}
		
		return array_filter($allBookingOffices, function($bookingOffice) {
			return ($bookingOffice->Identifier != BookingOfficeOptions::HOME_DELIVERY);
		});
	}
	
	public function getDefaultBoxOfficeOptionID() {
		//return BookingOfficeOptions::DEFAULT_BOX_OFFICE;
		
		$bookingOffices = $this->getBySessionID()->List;
		if (count($bookingOffices) <= 0) {
			return NULL;
		}
		
		foreach ($bookingOffices as $bookingOffice) {
			if ($bookingOffice->Identifier == BookingOfficeOptions::DEFAULT_BOX_OFFICE) {
				return $bookingOffice->Identifier;
			}
		}
		
		return $bookingOffices[0]->Identifier;
	}
	
	public function getDefaultHomeDeliveryOptionID() {
		return BookingOfficeOptions::HOME_DELIVERY;
	}
	
	public function getHomeDeliveryOptions($sessionID = NULL) {
		if (!isset($sessionID)) {
			$sessionID = $this->session->getIdentifier();
		}
		
		$allBookingOffices = $this->apiClient->bookingHelper()->getBookingOffices($sessionID)->List;
		return array_filter($allBookingOffices, function($bookingOffice) {
			return ($bookingOffice->Identifier == BookingOfficeOptions::HOME_DELIVERY);
		});
	}
	
	public function getOptionFromID($IDBookingOffice) {
		if ($IDBookingOffice == BookingOfficeOptions::HOME_DELIVERY) {
			return BookingOfficeOptions::HOME_DELIVERY;
		}
		
		return BookingOfficeOptions::BOX_OFFICE_PICKUP;
	}
	
	public function isHomeDeliveryEnabled($bookingOffices = NULL) {
		
		if ($bookingOffices == NULL) {
			$bookingOffices = $this->apiClient->bookingHelper()->getBookingOffices($this->session->getIdentifier())->List;
		}
		
		foreach ($bookingOffices as $bookingOffice) {
			if ($bookingOffice->Identifier == BookingOfficeOptions::HOME_DELIVERY) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function isBoxOfficePickupEnabled($bookingOffices = NULL) {
		if ($bookingOffices == NULL) {
			$bookingOffices = $this->apiClient->bookingHelper()->getBookingOffices($this->session->getIdentifier())->List;
		}
		
		foreach ($bookingOffices as $bookingOffice) {
			if ($bookingOffice->Identifier != BookingOfficeOptions::HOME_DELIVERY) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
}

?>
