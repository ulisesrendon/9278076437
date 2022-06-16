<?php

namespace Drupal\gv_fanatics_plus_order;

use Drupal\gv_fanatics_plus_order\Order;
use Drupal\Core\Url;

/**
 * Modelos representativos del historial de expedientes
 */
class OrderHistory {	
	private $apiClient;
	private $session;
	private $dateFormatter;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
		$this->dateFormatter = \Drupal::service('date.formatter');
	}
	
	public function getList($sessionID) {
		$list = $this->apiClient->webDataBooking()->getBookingList($sessionID)->List;
		if (!isset($list)) {
			return [];
		}
		
		$mappedList = array_map(function($bookingOrder) {
			$mapper = new \JsonMapper();
			$order = $mapper->map($bookingOrder, new Order());
			return $order;
		}, $list);
		
		return $mappedList;
	}
	
	public function getListDetail($sessionID, $pageNumber = 1, $pageSize = 10, $returnTotalPages = FALSE, $showOverdueOrders = NULL, $IDSeason = NULL) {
		$response = $this->apiClient->webDataBooking()->getBookingListDetail($sessionID, $pageNumber, $pageSize, $showOverdueOrders, $IDSeason);
		$list = $response->List;
		if (!isset($list)) {
			return [];
		}
		
		
		$languageResolver = \Drupal::service('gv_fplus.language_resolver');
		$IDLanguage = $languageResolver->resolve()->id();
				
		$bookingStatuses = $this->apiClient->core()->getBookingStatuses();

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
		
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		$mappedList = array_map(function($bookingOrder) use ($bookingStatusMap) {
			$mapper = new \JsonMapper();
			$mapper->bStrictNullTypes = false;
			$bookingStatusLabel = $bookingStatusMap[$bookingOrder->Booking->IDBookingStatus];
			$bookingOrder->Booking->BookingOpeningDateFormatted = $this->dateFormatter->format(strtotime($bookingOrder->Booking->BookingOpeningDate), 'custom', 'j F Y', NULL, $langCode); // date('d F Y', strtotime($bookingOrder->Booking->BookingOpeningDate));
			$bookingOrder->Booking->BookingOpeningDateHourFormatted = $this->dateFormatter->format(strtotime($bookingOrder->Booking->BookingOpeningDate), 'custom', 'H:i', NULL, $langCode); // date('h:i', strtotime($bookingOrder->Booking->BookingOpeningDate));
			$bookingOrder->Booking->BookingStatusLabel = $bookingStatusLabel;
			$bookingOrder->Booking->BookingDetailURL = Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $bookingOrder->Booking->Identifier]);
			$order = $mapper->map($bookingOrder->Booking, new Order());
			return $order;
		}, $list);
		
		if (!$returnTotalPages) {
			return $mappedList;
		}
		
		$returnVal = new \stdClass();
		$returnVal->List = $mappedList;
		$returnVal->TotalAmount = $response->TotalAmount;
		
		return $returnVal;
	}
}

?>
