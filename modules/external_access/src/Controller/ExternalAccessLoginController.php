<?php

namespace Drupal\gv_fanatics_plus_external_access\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controlador correspondiente a la ruta de acceso externo.
 */
class ExternalAccessLoginController extends ControllerBase {

	private $session;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		
		return new static($session);
	}
	
	public function __construct($session) {
		$this->session = $session;
	}

	public function access() {
		$currentRequest = \Drupal::request();
		
		$usToken = $currentRequest->query->get('us');
		$clToken = $currentRequest->query->get('cl');
		$texToken = $currentRequest->query->get('tex');
		$expToken = $currentRequest->query->get('exp');
		$url = $currentRequest->query->get('url');
		$tqusu = $currentRequest->query->get('tqusu');
		
		if ($usToken == NULL 
		|| $clToken == NULL
		|| $texToken == NULL) {
			$response = new RedirectResponse(Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString(TRUE)->getGeneratedUrl(), 307);
			\Drupal::messenger()->addMessage($this->t('An error ocurred in the external access flow, please verify your credentials', [], []), 'error');
			return $response;
			\Drupal::service('page_cache_kill_switch')->trigger();
			return [];
		}
		
		$loginResult = $this->session->externalAccess($usToken, $clToken, $texToken, $expToken, $tqusu);
		if ($loginResult == NULL) { // failure, invalid credentials			
			$response = new RedirectResponse(Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString(TRUE)->getGeneratedUrl(), 307);
			\Drupal::messenger()->addMessage($this->t('An error ocurred in the external access flow, please verify your credentials', [], []), 'error');
			return $response;
			\Drupal::service('page_cache_kill_switch')->trigger();
			return [];
		} else if (is_array($loginResult) && isset($loginResult['code']) && $loginResult['code'] == -2) {
			$email = $loginResult['email'];
			$this->session->start(TRUE);
			
			$sessionID = $this->session->getIdentifier();
			$languageID = $this->session->getIDLanguage();
			$salesChannelID = $this->session->getIDSalesChannel();
			$activationResponse = \Drupal::service('gv_fplus_auth.user')->sendActivation($email, $languageID, $salesChannelID);
			
			$currentRequest->getSession()->set('gv_fplus_activate_email', $email);
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.activation_email_sent_form')->toString(TRUE)->getGeneratedUrl(), 307);
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
		
		$accessType = $loginResult->IDAccesType;
		$IDBooking = $loginResult->IDBooking;
		$destinationURL = Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString(TRUE)->getGeneratedUrl();
		if ($IDBooking != NULL) {
			$orderID = $loginResult->IDBooking;
			$destinationURL = Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $orderID])->toString(TRUE)->getGeneratedUrl();
		} else if (isset($url) && strlen($url) > 0) {
			$response = new TrustedRedirectResponse(Url::fromUri($url)->toString(TRUE)->getGeneratedUrl(), 307);
			return $response;
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
		
		$response = new RedirectResponse($destinationURL, 307);
		return $response;
		\Drupal::service('page_cache_kill_switch')->trigger();
	}

}

?>
