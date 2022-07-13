<?php

namespace Drupal\gv_fanatics_plus_order;

use Drupal\gv_fanatics_plus_order\PendingData;

use Drupal\Core\Url;

/**
 * Modelos representativos de expedientes (Order) de Doblemente
 */

final class BookingServiceDocumentStatus {
	const PENDING_UPLOAD = 0;
	const PENDING_REVISION = 1;
	const ACCEPTED = 2;
	const REJECTED = 3;
}

class ServiceSeasonPassData {
	public $IDProduct;
	public $Product;
	public $IDInsurance;
	public $Insurance;
	public $IDClient;
	public $IDSeason;
	public $SerialNumber;
	public $WTPNumber;
	public $InsuranceAmount;
	public $SeasonPassAmount;
	public $Recharged;
	public $Rechargeable;
	public $Printed;
	public $InitialSale;
	
	/** @var BookingServiceSeasonPassDataAmountDetails[] **/
	public $AmountDetails;
	
	/** @var BookingServiceDocument[] **/
	public $Documents;
}

class BookingServiceDocument {
	public $Identifier;
	public $Titulo;
	public $Codigo;
	public $DescripcionInterna;
	public $DescripcionPublica;
	public $IDTipo;
	public $PalabrasClave;
	public $NombreArchivo;
	public $Estado;
	public $MotivoDenegado;
	
	public function isPending() {
		return ($this->Estado == BookingServiceDocumentStatus::PENDING_UPLOAD);
	}
}

class BookingServiceSeasonPassDataAmountDetails {
	public $Day;
	public $StartTime;
	public $SkiDays;
	public $DailyCost;
	public $HalfDayDiscount;
	public $AccumulatedDaysDiscount;
	public $FamilyDiscount;
	public $Savings;
	public $DailyInsuranceCost;
	public $InsuranceSaving;
	public $TotalSavings;
	public $FinalPrice;
	public $AmountCharged;
	public $InsuranceAmountCharged;
	public $TotalCharged;
	public $Members;
	public $SkiResort;
	public $TotalDays;
}

class ServiceCancellationFeesCancellationPenalty {
	public $StartDate;
	public $EndDate;
	public $Percent;
	public $NumberOfNights;
	public $Amount;
}

class ServiceCancellationFees {
	public $Identifier;
	public $IDCurrency;
	public $Amount;
	public $CancelPenaltiesStartDate;
	public $CancelPenaltiesDescription;
	
	/** @var ServiceCancellationFeesCancellationPenalty[] **/
	public $CancellationPenalties;
}

class OrderService {
	 public $Identifier;
	 public $Service;
	 public $IDServiceType;
	 public $IDServiceStatus;
	 public $IDProduct;
	 public $Product;
	 public $ProductCode;
	 public $IDSector;
	 public $StartDate;
	 public $EndDate;
	 public $CostAmount;
	 public $NetAmount;
	 public $SalesAmount;
	 public $IDSearch;
	 public $CreationDate;
	 public $BookingStatusLabel;
	 
	 /** @var ServiceSeasonPassData **/
	 public $SeasonPassData;
	
	 /** @var ServiceCancellationFees **/
	 public $CancellationFees;
	 public $IntegrantData;
	 
	 public function hasPendingDocuments() {
	 	if (!isset($this->SeasonPassData) 
	 		|| !isset($this->SeasonPassData->Documents) 
	 		|| count($this->SeasonPassData->Documents) <= 0) {
	 		return FALSE;
	 	}
	 	
	 	$documents = $this->SeasonPassData->Documents;
	 	foreach ($documents as $document) {
	 		if ($document->isPending()) {
	 			return TRUE;
	 		}
	 	}
	 	
	 	return FALSE;
	 }
	 
	 public function hasSeasonDataAmountDetails() {
	 	return (isset($this->SeasonPassData->AmountDetails) && (count($this->SeasonPassData->AmountDetails) > 0));
	 }
	 
	 public function getSeasonDataAmountDetailsURL($orderID) {
	 	return Url::fromRoute('gv_fanatics_plus_order.service_data_amount_details_modal', ['orderID' => $orderID, 'serviceID' => $this->Identifier]);
	 }
}

class OrderPayment {
	public $Identifier;
	public $PaymentDueDate;
	public $IDPaymentInstrument;
	public $IsRefund;
	public $Consolidated;
	public $Amount;
	
	public function getPaymentDueDate($formatted = FALSE) {
		if (!$formatted) {
			return $this->PaymentDueDate;
		}
		
		$dateFormatter = \Drupal::service('date.formatter');
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		if ($this->PaymentDueDate != NULL && strlen($this->PaymentDueDate) > 0 ) {
			return $dateFormatter->format(strtotime($this->PaymentDueDate), 'custom', 'j F Y', NULL, $langCode);
		} else {
			return '';
		}
	}
}

class Order implements OrderInterface {
	public $Identifier;
	public $BookingLocator;
	public $BookingOpeningDate;
	public $BookingOpeningDateFormatted;
	public $BookingOpeningDateHourFormatted;
	public $IDBookingStatus;
	public $BookingStatusLabel;
	public $BookingDetailURL;
	public $IDSession;
	public $IDSalesChannel;
	public $IDCurrency;
	public $IDLanguage;
	public $IDClient;
	public $IDUser;
	public $IDClientType;
	public $ConsumedDate;
	public $CostAmount;
	public $NetAmount;
	public $SalesAmount;
	public $BalanceAmount;
	public $SignatureRequired;
	public $IDSignatureStatus;
	public $Description;
	public $CancellationFees;
	public $IDBookingOffice;
	public $IDPaymentMethod;
	public $InitialSale;
	public $Integrants;
	public $OwnerIntegrant;
	public $LogaltyStatus;
	public $LogaltyStatusDetail;
	public $OverduePayment;
	
	public $PendingData;
	public $SynchronizedConversionScript;
	
	/** @var OrderService[] */
	public $Services;
	
	/** @var OrderPayment[] */
	public $Payments;
	
	private $apiClient;
	private $session;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function getPendingData() {
		$counter = 0;
		
		if (!$this->isInitialSale()) {
			$this->PendingData->Counter = $counter;
			return $this->PendingData;
		}
		
		if ($this->PendingData->PendingSignature) {
			++$counter;
		}
		
		if ($this->PendingData->OverduePayment) {
			++$counter;
		}
		
		foreach ($this->PendingData->Services as $service) {
			if ($service->PendingRecharge) {
				++$counter;
			}
			
			if ($service->PendingPhoto) {
				++$counter;
			}
		}
		
		if ($this->hasPendingShippingMethod()) {
			++$counter;
		}
		
		if ($this->PendingData->PendingDocuments) {
			++$counter;
		}

		//JMP : 05/12/21
		//NO MOSTRAR ALERTAS SI LO ÚNICO PENDIENTE ES LA PHOTO PARA FORFETS RECARGADOS O IMPRIMIDOS
		//ASUMIMOS QUE POR ERROR, SE MARCA COMO PENDINGDATA IMÁGENES DE FORFETS RECARGADOS Y/O IMPRIMIDOS.
		$recharged = false;
		$printed = false;

		foreach ($this->Services as $serv) {

			$recharged = $serv->SeasonPassData->Recharged;
			$printed = $serv->SeasonPassData->Printed;

			//Nueva sublógica. Puede tener recargado o impreso, pero si faltan documentos, debe advertirse al cliente.
			$hasPendingDocuments = FALSE;
			if (!isset($serv->SeasonPassData)
				|| !isset($serv->SeasonPassData->Documents)
				|| count($serv->SeasonPassData->Documents) <= 0) {
				continue;
			}

			$documents = $serv->SeasonPassData->Documents;
			foreach ($documents as $document) {
				if ($document->isPending()) {
					$hasPendingDocuments = TRUE;
					break;
				}
			}

			if (($printed || $recharged) && !$hasPendingDocuments) {
				--$counter;
			}
			else {}
		}

		$this->PendingData->Counter = $counter;
		return $this->PendingData;
	}
	
	public function isInitialSale() {
		return $this->InitialSale;
	}
	
	public function isPaid() {
		return ($this->IDBookingStatus == 3);
	}
	
	public function isConsumed() {
		foreach ($this->Services as $service) {
			if (!$service->SeasonPassData->Printed) { // one of the services is not printed, order is not consumed
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	public function bookingRechargeable() {
		$rechargeService = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		$bookingRechargeable = $rechargeService->bookingRechargeable($this->session->getIdentifier(), $this->Identifier);
		return $bookingRechargeable;
	}
	
	public function hasPendingShippingMethod() {
		
		if (!$this->isInitialSale()) {
			return FALSE;
		}
		
		$rechargeService = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		$allRecharges = TRUE;
		$allRecharged = TRUE;
		$allPrinted = TRUE;
		$bookingRechargeable = $rechargeService->bookingRechargeable($this->session->getIdentifier(), $this->Identifier);
		// $bookingRechargeable->Rechargeable
		foreach ($bookingRechargeable->Services as $service) {
			if (!$service->RechargeRequest && !$service->Recharged && !$service->Rechargeable) {
				$allRecharges = FALSE;
			}
			
			if (!$service->Recharged) {
				$allRecharged = FALSE;
			}
			
			if (!$service->Printed) {
				$allPrinted = FALSE;
			}
		}
		
		return !((($allRecharges && $bookingRechargeable->Rechargeable) || $allRecharged) || isset($this->IDBookingOffice) || $allPrinted == TRUE);
	}
	
	public function hasPendingDocuments() {
		$hasPendingDocuments = FALSE;
		foreach ($this->Services as $index => $service) {
			if (!isset($service->SeasonPassData) 
			|| !isset($service->SeasonPassData->Documents) 
			|| count($service->SeasonPassData->Documents) <= 0) {
				continue;
			}
			
			$documents = $service->SeasonPassData->Documents;
			foreach ($documents as $document) {
				if ($document->isPending()) {
					$hasPendingDocuments = TRUE;
					break;
				}
			}
		}
		
		return $hasPendingDocuments;
	}
	
	public function getFirstOverduePayment() {
		foreach ($this->Payments as $payment) {
			if (!$payment->Consolidated && !$payment->IsRefund) {
				return $payment;
			}
		}
		
		return NULL;
	}
	
	public function isSignatureRequired() {
		return $this->SignatureRequired;
	}
	
	public function getPostPaymentURL() {
		return Url::fromRoute('gv_fanatics_plus_checkout.post_payment', ['orderID' => $this->Identifier]);
	}
	
	public static function getDefaultUserAvatar() {
		return '/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/user-line-white--default-avatar.svg';
	}
	
	public function getIDFromLocator($bookingLocator) {
		$response = $this->apiClient->booking()->getByLocator($this->session->getIdentifier(), $bookingLocator);
		if (!isset($response)) {
			return NULL;
		}
		
		return $response->Booking;
	}
	
	public static function getFromID($orderID, $loadClients = FALSE, $mapToModel = FALSE) {
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		$image = \Drupal::service('gv_fplus_auth.image');
		$dateFormatter = \Drupal::service('date.formatter');
		$integrantService = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		if ($session->getIdentifier() == NULL) {
			return NULL;
		}
		
		$response = $apiClient->booking()->getByID($session->getIdentifier(), $orderID);
		
		if(\Drupal::currentUser()->id() == 10){
		    ksm($response->Booking->Services, $response->Booking->PendingData);
		}
		
		if ($loadClients) {
			$clientID = $response->Booking->IDClient;
			$integrants = $apiClient -> integrants() -> listMembers($clientID) -> List;
			$integrantMap = [];
			
			foreach ($integrants as $index => $integrant) {
				$integrantMap[$integrant->IntegrantID] = $integrant;
			}
			
			foreach ($response->Booking->Services as $index => $service) {
				$IDClient = $service->SeasonPassData->IDClient;
				if ($IDClient != NULL) {
					$integrantProfile = $user->getUserProfileByClientID($IDClient, TRUE, TRUE);
					$integrantProfile->ImageBase64 = $integrantProfile->Image;
					$integrantProfile->ImageCanEdit = $integrantProfile->CanEditImage;	
				}
				
				// Set default avatar
				if (!isset($integrantProfile->ImageBase64)) {}
				
				$response->Booking->Services[$index]->IntegrantData = $integrantProfile;
				if ($IDClient == $response->Booking->IDClient || $IDClient == NULL) {
					$ownerProfile = $user->getProfileByID($response->Booking->IDUser, TRUE, TRUE);

					$ownerProfileImage = new \stdClass();
					$ownerProfileImage->ImageBase64 = $ownerProfile->Image;
					$ownerProfileImage->Expired = $ownerProfile->ImageExpired;
					$ownerProfileImage->CanEdit = $ownerProfile->CanEditImage;
					if (!isset($ownerProfileImage->ImageBase64)) {}

					if (isset($ownerProfileImage)) {
						$ownerProfile->ImageBase64 = $ownerProfileImage->ImageBase64;
						$ownerProfile->ImageExpired = $ownerProfileImage->Expired;
						$ownerProfile->ImageCanEdit = $ownerProfileImage->CanEdit;
					} else {
						$ownerProfile->ImageBase64 = NULL;
						$ownerProfile->ImageExpired = FALSE;
						$ownerProfile->ImageCanEdit = FALSE;
					}
					
					$response->Booking->Services[$index]->IntegrantData = $ownerProfile;
					$response->Booking->OwnerIntegrant = $ownerProfile;
				}
			}
			
			$response->Booking->Integrants = $integrants;
		}
		
		if (!$mapToModel) {
			return $response;
		}
		
		$languageResolver = \Drupal::service('gv_fplus.language_resolver');
		$IDLanguage = $languageResolver->resolve()->id();
				
		$bookingStatuses = $apiClient->core()->getBookingStatuses();
		$bookingStatusMap = [];
		foreach ($bookingStatuses as $bookingStatus) {
			$statusLabel = $bookingStatus->Status;
			if (isset($IDLanguage)) { //JMP, anulamos si el idioma es 1. Siempre debe traducirse.
				foreach($bookingStatus->Translations as $translation) {
					if ($IDLanguage == $translation->IDLanguage) {
						$statusLabel = $translation->Translation;
						break;
					}
				}
			}
			$bookingStatusMap[intval($bookingStatus->Identifier)] = $statusLabel;
		}
		
		$bookingServiceStatuses = $apiClient->core()->getBookingServiceStatuses();
		$bookingServiceStatusMap = [];
		foreach ($bookingServiceStatuses as $bookingServiceStatus) {
			$bookingServiceStatusMap[intval($bookingServiceStatus->Identifier)] = $bookingServiceStatus->Status;
		}
		
		$mapper = new \JsonMapper();
		$mapper->bStrictNullTypes = false;
		
		$bookingStatusLabel = $bookingStatusMap[$response->Booking->IDBookingStatus];
		foreach ($response->Booking->Services as $index => $service) {
			$bookingServiceStatusLabel = $bookingServiceStatusMap[$service->IDServiceStatus];
			$response->Booking->Services[$index]->BookingStatusLabel = $bookingServiceStatusLabel;
		}
				
		$response->Booking->BookingOpeningDateFormatted = $dateFormatter->format(strtotime($response->Booking->BookingOpeningDate), 'custom', 'j F Y', NULL, $langCode);
		$response->Booking->BookingOpeningDateHourFormatted = $dateFormatter->format(strtotime($response->Booking->BookingOpeningDate), 'custom', 'H:i', NULL, $langCode);
		$response->Booking->BookingStatusLabel = $bookingStatusLabel;
		
		$order = $mapper->map($response->Booking, new Order());
		return $order;
	}

	public static function getOrder() {
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		$image = \Drupal::service('gv_fplus_auth.image');
		$dateFormatter = \Drupal::service('date.formatter');
		$integrantService = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		if ($session->getIdentifier() == NULL) {
			return NULL;
		}
		

		ksm($apiClient->booking());

		return $apiClient->booking()->getByID($session->getIdentifier(), $orderID);
	}

	public static function editBookingOffice($IDBooking, $IDBookingOffice, $IDSession = NULL) {
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$session = \Drupal::service('gv_fplus.session');
		
		if (!isset($IDSession)) {
			$IDSession = $session->getIdentifier();
		}
		
		return $apiClient->booking()->editBookingOffice($IDBooking, $IDSession, $IDBookingOffice);
	}
	
	public static function editBookingSynchronizedConversionScript($IDBooking, $sync, $IDSession = NULL) {
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$session = \Drupal::service('gv_fplus.session');
		
		if (!isset($IDSession)) {
			$IDSession = $session->getIdentifier();
		}
		
		return $apiClient->booking()->editBookingSynchronizedConversionScript($IDBooking, $IDSession, $sync);
	}
}

?>
