<?php

namespace Drupal\gv_fanatics_plus_cart;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\gv_fanatics_plus_cart\Event\CartBookingServiceAddEvent;
use Drupal\gv_fanatics_plus_cart\Event\CartBookingServiceRemoveEvent;
use Drupal\gv_fanatics_plus_cart\Event\CartBookingInsuranceAddEvent;
use Drupal\gv_fanatics_plus_cart\Event\CartBookingInsuranceRemoveEvent;
use Durpal\gv_fanatics_plus_cart\Event\CartBookingServiceConfirmEvent;
use Drupal\gv_fanatics_plus_cart\Event\CartEvents;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Drupal\gv_fanatics_plus_order\Order;

/**
 * Representa la entidad del carrito
 */
class Cart implements CartInterface {

	private $session;
	private $apiClient;
	private $image;
	private $user;

	/**
	 * The event dispatcher.
	 *
	 * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
	 */
	protected $eventDispatcher;

	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->image = \Drupal::service('gv_fplus_auth.image');
		$this->user = \Drupal::service('gv_fplus_auth.user');
		$this->eventDispatcher = \Drupal::service('event_dispatcher');
	}

	/**
	 * Retorna el carrito actual
	 */
	public function getCurrent() {
		return $this -> apiClient -> booking() -> getCurrent($this -> session -> getIdentifier());
	}
	
	/**
	 * Retorna el carrito actual con más información
	 * 
	 * @param $loadClients TRUE para retornar la información de clientes asociados al servicio, FALSE en caso contrario
	 */
	public function getCurrentDetail($loadClients = FALSE) {
		$response =  $this -> apiClient -> booking() -> getCurrentDetail($this -> session -> getIdentifier());
		if (!$loadClients) {
			return $response;
		}
		
		$clientID = $response->Booking->IDClient;
		
		foreach ($response->Booking->Services as $index => $service) {
			$IDClient = $service->SeasonPassData->IDClient;
			$integrantProfile = $this->user->getUserProfileByClientID($IDClient, TRUE);
			$integrantProfile->ImageBase64 = $integrantProfile->Image;
			
			// Set default avatar
			if (!isset($integrantProfile->ImageBase64)) {
				$integrantProfile->ImageBase64 = Order::getDefaultUserAvatar();
			}
			
			$response->Booking->Services[$index]->IntegrantData = $integrantProfile;
			/*if (isset($integrantMap[$IDClient])) {
				$response->Booking->Services[$index]->IntegrantData = $integrantMap[$IDClient];
			} else */
			if ($IDClient == $response->Booking->IDClient) {
				$ownerProfile = $this->user->getProfileByID($response->Booking->IDUser);
				$ownerProfileImage = $this->image->getBySessionID($this->session->getIdentifier());
				if (isset($ownerProfileImage)) {
					$ownerProfile->ImageBase64 = $ownerProfileImage->ImageBase64;
				} else {
					$ownerProfile->ImageBase64 = NULL;
				}
				
				$response->Booking->Services[$index]->IntegrantData = $ownerProfile;
				$response->Booking->OwnerIntegrant = $ownerProfile;
			}
		}
		
		$response->Booking->Integrants = $integrants;
		
		return $response;
	}

	/**
	 * Determina si el carrito tiene servicios
	 */
	public function hasBookingServices() {
		if ($this->session->getIDUser() == NULL) {
			return FALSE;
		}
		
		$response =  $this -> apiClient -> booking() -> getCurrentDetail($this -> session -> getIdentifier());
		return (count($response->Booking->Services) > 0);
	}
	
	/**
	 * Retorna un servicio con base en un ID de cliente
	 * 
	 * @param $clientID ID de cliente
	 */
	public function getBookingService($clientID) {
		$bookingDetail = $this->getCurrentDetail()->Booking;
		foreach ($bookingDetail->Services as $index => $service) {
			$bookingServiceID = $service->Identifier;
			$serviceClientID = $service->SeasonPassData->IDClient;
			
			if ($serviceClientID == $clientID) {
				return $service;
			}
		}
		
		return NULL;
	}

	/**
	 * Añade un servicio al carrito del usuario
	 * 
	 * @param $searchID Identificador de búsqueda de Doblemente
	 * @param $productBookingCode Código de producto de Doblemente
	 * @param $quantity cantidad del producto a añadir, por omisión es 1
	 */
	public function addBookingService($searchID, $productBookingCode, $quantity = 1) {
		try {
			$sessionID = $this -> session -> getIdentifier();
			$response = $this -> apiClient -> booking() -> addService($sessionID, $searchID, intval($productBookingCode));
			$this->eventDispatcher->dispatch(CartEvents::CART_BOOKING_SERVICE_ADD, new CartBookingServiceAddEvent($this, $productBookingCode, $quantity));
			return $response;
		} catch(\GuzzleHttp\Exception\ServerException $e) {
			return FALSE;
		} catch (Exception $e) {
			return FALSE;
		}
		
	}

	/**
	 * Elimina un servicio del carrito
	 * 
	 * @param $bookingServiceID identificador del servicio a eliminar
	 */
	public function removeBookingService($bookingServiceID) {
		$response = $this -> apiClient -> booking() -> deleteService($this -> session -> getIdentifier(), $bookingServiceID);
		$this->eventDispatcher->dispatch(CartEvents::CART_BOOKING_SERVICE_REMOVE, new CartBookingServiceRemoveEvent($this, $bookingServiceID));
		return $response;
	}
	
	/**
	 * Retorna los seguros de un servicio
	 * 
	 * @param $bookingServiceID identificador de un servicio
	 */
	public function getSeasonPassInsurances($bookingServiceID) {
		$response = $this -> apiClient -> bookingComplementarySale() -> getByID($this -> session -> getIdentifier(), $bookingServiceID)->SeasonPassInsurances;
		return $response;
	}
	
	/**
	 * Añade un seguro a un servicio
	 * 
	 * @param $bookingServiceID identificador del servicio a lo cuál se debe añadir el seguro
	 * @param $insuranceID identificador del seguro a añadir
	 */
	public function addSeasonPassInsurance($bookingServiceID, $insuranceID) {
		$response = $this -> apiClient -> bookingComplementarySale() -> addSeasonPassInsurance($this -> session -> getIdentifier(), $bookingServiceID, $insuranceID);
		$this->eventDispatcher->dispatch(CartEvents::CART_BOOKING_SERVICE_INSURANCE_ADD, new CartBookingInsuranceAddEvent($this, $bookingServiceID, $insuranceID));
		return $response;
	}
	
	/**
	 * Remueve un seguro de un servicio
	 * 
	 * @param $bookingServiceID identificador del servicio de lo cuál se debe remover el seguro
	 */
	public function removeSeasonPassInsurance($bookingServiceID) {
		$response = $this -> apiClient -> bookingComplementarySale() -> deleteSeasonPassInsurance($this -> session -> getIdentifier(), $bookingServiceID);
		$this->eventDispatcher->dispatch(CartEvents::CART_BOOKING_SERVICE_INSURANCE_REMOVE, new CartBookingInsuranceRemoveEvent($this, $bookingServiceID));
		return $response;
	}

	/**
	 * Confirma el expediente correspondiente al pedido actual
	 * 
	 * @param $paymentMethodID Identificador del método de pago
	 * @param $clientReference Referencia del cliente
	 * @param $bookingOfficeID Identificador de taquilla del expediente
	 * @param $relatedBookingLocator Identificador del localizador de referencia (MemberGetMember)
	 * @param $internalNotes Notas internas del expediente
	 */
	public function confirmBooking($paymentMethodID, $clientReference = NULL, $bookingOfficeID = NULL, $relatedBookingLocator = NULL, $internalNotes = NULL) {
		$bookingResult = $this -> apiClient -> booking() -> confirm($this -> session -> getIdentifier(), $paymentMethodID, $clientReference, $bookingOfficeID, $relatedBookingLocator, $internalNotes);
		$this->eventDispatcher->dispatch(CartEvents::CART_BOOKING_SERVICE_CONFIRM, new CartBookingServiceConfirmEvent($this, $bookingResult));
		return $bookingResult;
	}
	
	/**
	 * Retorn los métodos de pago disponibles
	 */
	public function getAvailablePaymentMethods() {
		return $this->apiClient->bookingHelper()->getPaymentMethods($this->session->getIdentifier());
	}
	
	/**
	 * Determina si el carrito es editable (de muestran los controles correspondientes en el bloque de carrito)
	 * 
	 * @param $currentCheckoutStepId identificador de paso de checkout a evaluar
	 */
	public function isEditable($currentCheckoutStepId) {
		if ($currentCheckoutStepId != CheckoutOrderSteps::PROFILE_DATA && $currentCheckoutStepId != CheckoutOrderSteps::PRODUCT_SELECTION) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Retorna el booking referral
	 */
	public function getBookingReferral() {
		return $this->session->getBookingReferral();
	}
	
	/**
	 * Guarda un booking referral
	 */
	public function saveBookingReferral($bookingReferral) {
		$this->session->saveBookingReferral($bookingReferral);
	}
	
	/**
	 * Elimina un booking referral
	 */
	public function deleteBookingReferral() {
		$this->session->deleteBookingReferral();
	}
}

?>
