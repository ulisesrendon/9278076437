<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador de historial de expedientes
 */
class OrderHistoryController extends ControllerBase {

	private $apiClient;
	private $orderHistory;
	private $session;
	private $user;
	private $channelResolver;
	private $translationService;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$orderHistory = $container->get('gv_fanatics_plus_order.order_history');
		$user = $container->get('gv_fplus_auth.user');
		$channelResolver = $container->get('gv_fplus.channel_resolver');
		
		return new static($apiClient, $session, $orderHistory, $user, $channelResolver);
	}
	
	public function __construct($apiClient, $session, $orderHistory, $user, $channelResolver) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->orderHistory = $orderHistory;
		$this->user = $user;
		$this->channelResolver = $channelResolver;
		$this->translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
	}

	public function orderHistory() {
		$currentRequest = \Drupal::request();	
		
		// add variable: pageNumber
		// add variable pageSize: 15
		
		$pageNumber = 1;
		$pageNumberQuery = $currentRequest->query->get('pageno');
		if ($pageNumberQuery != NULL && is_numeric($pageNumberQuery)) {
			$pageNumber = $pageNumberQuery;
		}
		
		$pageSize = 10;
		
		$defaulting = FALSE;
		$defaultingParam = $currentRequest->query->get('defaulting');
		$seasonParam = $currentRequest->query->get('season');
		$seasons = $this->user->getSeasons();
		if ($seasons != NULL) {
			$seasons = $seasons->List;
		}
		
		if ($seasonParam == NULL && count($seasons) > 0) {
			$seasonParam = $seasons[count($seasons) - 1]->Identifier;
		}
		
		$userEmail = $this->session->getEmail();
		if (isset($userEmail)) {
			$defaulting = $this->user->isDefaulting($userEmail);
		} else if ($defaultingParam != NULL && $defaultingParam == 1) {
			$defaulting = TRUE;
		}
		
		if ($defaulting) {
			\Drupal::messenger()->addMessage($this->translationService->translate('AUTH.OVERDUE_PAYMENTS_REDIRECT_MESSAGE'), 'warning');
			$seasonParam = NULL;
			$seasons = [];
		}
		
		$sessionID = $this->session->getIdentifier();
		$response = $this->orderHistory->getListDetail($sessionID, $pageNumber, $pageSize, TRUE, $defaulting, $seasonParam);
		
		$orders = $response->List;
		$totalOrdersCount = $response->TotalAmount;
		
		//$totalOrdersCount = 50;
		$totalNumPages = ceil($totalOrdersCount / $pageSize);
		$pages = range(1, $totalNumPages);
		
		$showPrevious = ($totalNumPages > 1 && $pageNumber > 1);
		$showNext = ($totalNumPages > 1 && $pageNumber < $totalNumPages);
		
		$processedSeasons = [];
		$defaultQueryParams = [];
		if ($defaulting) {
			$defaultQueryParams['defaulting'] = 1;
		}
		
		$baseRoute = 'gv_fanatics_plus_order.order_history';
		$currentSeason = NULL;
		foreach ($seasons as $index => $season) {
			$processedSeasons[$index] = [
				'identifier' => $season->Identifier,
				'season' => $season->Season,
				'url' => Url::fromRoute($baseRoute, [], ['query' => $defaultQueryParams + ['season' => $season->Identifier]])->toString(),
				'active' => FALSE
			];
			
			if ($season->Identifier == $seasonParam || ($seasonParam == NULL && $index == (count($seasons) - 1))) {
				$processedSeasons[$index]['active'] = TRUE;
				$currentSeason = $season->Identifier;
			}
		}

		$activeChannel = $this->channelResolver->resolve();
		return [
			'#attached' => [
				'library' => ['gv_fanatics_plus_order/history']
			],
			'#theme' => 'gv_fanatics_plus_order_history', 
			'#orders' => $orders,
			'#defaulting' => $defaulting,
			'#pages' => $pages,
			'#page_show_previous' => $showPrevious,
			'#page_show_next' => $showNext,
			'#page_current' => $pageNumber,
			'#sales_channel' => $activeChannel,
			'#seasons' => $processedSeasons,
			'#current_season' => $currentSeason
		];
	}

}

?>
