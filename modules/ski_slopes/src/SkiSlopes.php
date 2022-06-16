<?php

namespace Drupal\gv_fanatics_plus_ski_slopes;

use Drupal\Core\Url;

/**
 * Modelos representativos de datos de esquiadas de Doblemente
 */
class SkiDailyDate {
	private $season;
	public $Day;
	
	/** @var SkiDaily[] **/
	public $List;
	
	public $Count;
	public $Response;
	
	public function getDay($formatted = FALSE) {
		if (!$formatted) {
			return $this->Day;
		}
		
		$dateFormatter = \Drupal::service('date.formatter');
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		return $dateFormatter->format(strtotime($this->Day), 'custom', 'j F', NULL, $langCode);
	}
	
	public function getTotalSlope() {
		$totalSlope = 0;
		foreach ($this->List as $skiDaily) {
			$totalSlope += $skiDaily->getSlope();
		}
		
		return $totalSlope;
	}
	
	public function setSeason($season) {
		$this->season = $season;
	}
	
	public function getSeason() {
		return $this->season;
	}
	
	public function getDetailsURL($defaultQueryParams = NULL) {
		$queryParams = [];
		if ($defaultQueryParams != NULL) {
			$queryParams = $defaultQueryParams + $queryParams;
		}
		$baseRoute = 'gv_fanatics_plus_ski_slopes.detail';
		if ($queryParams['integrant'] != NULL) {
			$baseRoute = 'gv_fanatics_plus_ski_slopes.detail_integrant';
		}
		
		return Url::fromRoute($baseRoute, ['season' => $this->season, 'day' => $this->Day], ['query' => $queryParams]);
	}
}

class SkiDaily {
	public $UseTime;
	public $chairliftName;
	public $InitHeight;
	public $FinalHeight;
	public $MinutesUse;
	
	public function getSlope() {
		return ($this->FinalHeight - $this->InitHeight);
	}
	
	public function getUseTime($formatted = FALSE) {
		if (!$formatted) {
			return $this->UseTime;
		}
		
		$dateFormatter = \Drupal::service('date.formatter');
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		return $dateFormatter->format(strtotime($this->UseTime), 'custom', 'H:i', NULL, $langCode);
	}
	
	public function getExitUseTime() {
		$dateTime = new \DateTime($this->UseTime);
		$minutesToAdd = ceil($this->MinutesUse);
		$dateTime->modify("+{$minutesToAdd} minutes");
		
		$dateFormatter = \Drupal::service('date.formatter');
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		return $dateFormatter->format($dateTime->getTimestamp(), 'custom', 'H:i', NULL, $langCode);
	}
}

class SeasonSkiDescents {
	public $Identifier;
	public $Season;
	public $Days;
}

class SkiSlopes {	
	private $apiClient;
	private $session;
	
	public function __construct() {
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->session = \Drupal::service('gv_fplus.session');
	}
	
	public function getBySession($IDIntegrant = NULL) {
		$list = $this->apiClient->skiSlopes()->getAll($this->session->getIdentifier(), $IDIntegrant)->List;
		$mappedList = array_map(function($listElem) {
			$mapper = new \JsonMapper();
			$mapper->bStrictNullTypes = false;
			$mappedListElem = $mapper->map($listElem, new SeasonSkiDescents());
			return $mappedListElem;
		}, $list);
		return $mappedList;
	}
	
	public function getByDay($skiDates, $IDIntegrant = NULL) {
		$list = $this->apiClient->skiSlopes()->getByDay($this->session->getIdentifier(), $skiDates, $IDIntegrant)->List;
		$mappedList = array_map(function($listElem) {
			$mapper = new \JsonMapper();
			$mapper->bStrictNullTypes = false;
			$mappedListElem = $mapper->map($listElem, new SkiDailyDate());
			return $mappedListElem;
		}, $list);
		return $mappedList;
	}
}
