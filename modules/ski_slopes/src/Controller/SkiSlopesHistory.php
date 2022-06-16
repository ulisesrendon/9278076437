<?php

namespace Drupal\gv_fanatics_plus_ski_slopes\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\gv_fanatics_plus_utils\Crypto;

/**
 * Ruta del historial de datos de esquiadas
 */
class SkiSlopesHistory extends ControllerBase {

	private $skiSlopes;
	private $session;
	private $integrant;
	private $user;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$skiSlopes = $container->get('gv_fanatics_plus_ski_slopes.ski_slopes');
		$integrant = $container->get('gv_fanatics_plus_checkout.integrant');
		$user = $container->get('gv_fplus_auth.user');
		
		return new static($skiSlopes, $session, $integrant, $user);
	}
	
	public function __construct($skiSlopes, $session, $integrant, $user) {
		$this->skiSlopes = $skiSlopes;
		$this->session = $session;
		$this->integrant = $integrant;
		$this->user = $user;
	}

	public function show() {
		$integrantID = NULL;
		$integrant = NULL;
		$integrantQueryParam = \Drupal::request()->query->get('integrant');
		if ($integrantQueryParam != NULL) {
			$integrantID = Crypto::decrypt($integrantQueryParam);
			$integrant = $this->user->getUserProfileByClientID($integrantID);
		}
		
		$skiSlopesData = $this->skiSlopes->getBySession($integrantID);
		$lastSkiDataSeason = array_values(array_slice($skiSlopesData, -1))[0];

		$lastSkiDataSeasonDetail = $this->skiSlopes->getByDay($lastSkiDataSeason->Days, $integrantID);
		
		$selectedSeason = \Drupal::request()->query->get('season');
		if ($selectedSeason == NULL) {
			$selectedSeason = $lastSkiDataSeason->Season;
		} else {
			$newLastSkiDataSeason = array_filter(array_values($skiSlopesData), function($slopeData) use ($selectedSeason) {
				return $slopeData->Season == $selectedSeason;
			});
			
			if ($newLastSkiDataSeason != NULL && count($newLastSkiDataSeason) > 0) {
				$lastSkiDataSeason = array_pop(array_reverse($newLastSkiDataSeason));
				$selectedSeason = $lastSkiDataSeason->Season;
				$lastSkiDataSeasonDetail = $this->skiSlopes->getByDay($lastSkiDataSeason->Days, $integrantID);
			}
		}
		
		$isSelectedSeasonValid = FALSE;
		foreach($skiSlopesData as $data) {
			if ($data->Season == $selectedSeason) {
				$isSelectedSeasonValid = TRUE;
			}
		}
		
		if (!$isSelectedSeasonValid && count($skiSlopesData) > 0) {
			throw new NotFoundHttpException();
		}
		
		foreach($lastSkiDataSeasonDetail as $data) {
			$data->setSeason($selectedSeason);
		}
		
		$defaultQueryParams = [];
		$baseRoute = 'gv_fanatics_plus_ski_slopes.history';
		if ($integrantID != NULL) {
			$defaultQueryParams['integrant'] = Crypto::encrypt($integrantID);
			$baseRoute = 'gv_fanatics_plus_ski_slopes.history_integrant';
		}
		
		$seasons = [];
		foreach ($skiSlopesData as $data) {
			$isActive = FALSE;
			if ($data->Season == $selectedSeason) {
				$isActive = TRUE;
			}
			//$seasons[] = $data->Season;
			$seasons[$data->Season] = [
				'label' => $data->Season,
				'active' => $isActive,
				'url' => Url::fromRoute($baseRoute, [], ['query' => $defaultQueryParams + ['season' => $data->Season]])->toString()
			];
		}

		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_ski_slopes/history'
				], 
			],
			'#ski_slopes_data' => $lastSkiDataSeasonDetail,
			'#seasons' => $seasons,
			'#is_integrant' => ($integrantID != NULL),
			'#integrant' => $integrant,
			'#default_query_params' => $defaultQueryParams,
			'#theme' => 'gv_fanatics_plus_ski_slopes_history'
		];
	}
}
