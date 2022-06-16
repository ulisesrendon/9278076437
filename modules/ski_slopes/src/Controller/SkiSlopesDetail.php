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
 * Ficha de detalle de datos de esquiadas
 */
class SkiSlopesDetail extends ControllerBase {

	private $skiSlopes;
	private $session;
	private $user;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$skiSlopes = $container->get('gv_fanatics_plus_ski_slopes.ski_slopes');
		$user = $container->get('gv_fplus_auth.user');
		
		return new static($skiSlopes, $session, $user);
	}
	
	public function __construct($skiSlopes, $session, $user) {
		$this->skiSlopes = $skiSlopes;
		$this->session = $session;
		$this->user = $user;
	}

	public function show(RouteMatchInterface $route_match) {
		$integrantID = NULL;
		$integrant = NULL;
		$integrantQueryParam = \Drupal::request()->query->get('integrant');
		if ($integrantQueryParam != NULL) {
			$integrantID = Crypto::decrypt($integrantQueryParam);
			$integrant = $this->user->getUserProfileByClientID($integrantID);
		}
		
		$season = $route_match->getParameter('season');
		$day = $route_match->getParameter('day');
		
		$skiSlopesData = $this->skiSlopes->getBySession($integrantID);
		
		$isSelectedSeasonValid = FALSE;
		foreach($skiSlopesData as $data) {
			if ($data->Season == $season) {
				$isSelectedSeasonValid = TRUE;
			}
		}
		
		if (!$isSelectedSeasonValid) {
			throw new NotFoundHttpException();
		}
		
		$allDays = $this->skiSlopes->getByDay([$day], $integrantID);
		$lastSkiDataSeasonDetail = array_values(array_slice($allDays, -1))[0];
		
		$dateFormatter = \Drupal::service('date.formatter');
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		$isSelectedDayValid = FALSE;
		
		$defaultQueryParams = [];
		$baseRoute = 'gv_fanatics_plus_ski_slopes.detail';
		if ($integrantID != NULL) {
			$defaultQueryParams['integrant'] = Crypto::encrypt($integrantID);
			$baseRoute = 'gv_fanatics_plus_ski_slopes.detail_integrant';
		}
		
		$seasons = [];
		$days = [];
		$currentDayLabel = '';
		foreach ($skiSlopesData as $data) {
			$isActive = FALSE;
			if ($data->Season == $season) {
				$isActive = TRUE;
				
				foreach ($data->Days as $dayElem) {
					$isDayActive = FALSE;
					if ($dayElem == $day) {
						$isDayActive = TRUE;
						$isSelectedDayValid = TRUE;
						$currentDayLabel = $dateFormatter->format(strtotime($dayElem), 'custom', 'd/m/Y', NULL, $langCode);
					}
					
					$days[$dayElem] = [
						'label' => $dateFormatter->format(strtotime($dayElem), 'custom', 'd/m/Y', NULL, $langCode),
						'active' => $isDayActive,
						'url' => Url::fromRoute($baseRoute, ['season' => $data->Season, 'day' => $dayElem], ['query' => $defaultQueryParams])->toString()
					];
				}
			}
			
			$seasons[$data->Season] = [
				'label' => $data->Season,
				'active' => $isActive,
				'url' => Url::fromRoute($baseRoute, ['season' => $data->Season, 'day' => $day], ['query' => $defaultQueryParams])->toString()
			];
			
			if (!$isActive) {
				$seasons[$data->Season]['url'] = Url::fromRoute($baseRoute, ['season' => $data->Season, 'day' => $data->Days[0]], ['query' => $defaultQueryParams])->toString();
			}
		}
		
		if (!$isSelectedDayValid) {
			throw new NotFoundHttpException();	
		}
		
		$totalSlope = 0;
		$graphLabels = [];
		$graphData = [];
		foreach($lastSkiDataSeasonDetail as $data) {
			foreach ($data as $day) {
				$graphLabels[] = $day->chairliftName;
				$graphLabels[] = $day->chairliftName;
			
				$graphData[] = $day->InitHeight;
				$graphData[] = $day->FinalHeight;
				
				$totalSlope += $day->getSlope();
			}
		}
		
		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_ski_slopes/detail'
				],
			],
			'#ski_slopes_data' => $lastSkiDataSeasonDetail,
			'#graph_labels' => $graphLabels,
			'#graph_data' => $graphData,
			'#seasons' => $seasons,
			'#days' => $days,
			'#total_slope' => $totalSlope,
			'#current_day_label' => $currentDayLabel,
			'#is_integrant' => ($integrantID != NULL),
			'#integrant' => $integrant,
			'#theme' => 'gv_fanatics_plus_ski_slopes_detail'
		];
	}

}
