<?php

namespace Drupal\gv_fplus_auth;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Url;

use \Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerInterface;

use Drupal\Component\Utility\Timer;

use Drupal\gv_fplus\TranslationContext;

/**
 * Suscriptor responsable por ejecutar el ciclo de vida de sesión y otras verificaciones importantes.
 * Similar al hook_init de Drupal 7.
 */
class AuthSubscriber implements EventSubscriberInterface {
	use StringTranslationTrait;
	  
	private $session;
	private $apiClient;
	private $channelResolver;
	private $languageResolver;
	private $user;
	private $drupalUser;
	private $routerAdminContext;
	private $metricsCollector;
	private $translationService;
	
	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
		$this->apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$this->languageResolver = \Drupal::service('gv_fplus.language_resolver');
		$this->user = \Drupal::service('gv_fplus_auth.user');
		$this->drupalUser = \Drupal::currentUser();
		$this->routerAdminContext = \Drupal::service('router.admin_context');
		$this->metricsCollector = \Drupal::service('gv_fanatics_plus_metrics_collector.metrics_collector');
		$this->translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
	}
	
	private function _isAdminRoute() {
		return $this->routerAdminContext->isAdminRoute();
	}
	
  	public function setupDbmSession(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		if (!isset($activeChannel) || $this->_isAdminRoute()) {
			return;
		}
		
		$metricsCollector = $this->metricsCollector;
		$this->apiClient->setStatsSubscriber($metricsCollector);
		
		$metricsCollector->setNamespace('my_grandski_session');
		$metricsCollector->setStep('');
		
		// Only start a DBM session if a sales channel is resolved		
		$this->session->start();
		
		Timer::start('gv_fanatics_plus_page_load_time');
  	}
	
	public function checkForExternalLogin(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		if (!isset($activeChannel)) {
			return;
		}
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET' || $this->_isAdminRoute()) {
			return;
		}
		
		$externalSessionID = $currentRequest->query->get('dbmsessionid');
		if (!isset($externalSessionID)) {
			return;
		}
		
		try {
			$response = $this->session->externalLogin($externalSessionID);
		} catch (\Exception $e) {
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
		}
	}
	
	public function targetLanguageRedirection(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		if (!isset($activeChannel)) {
			return;
		}
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET' || $this->_isAdminRoute()) {
			return;
		}

		$languageID = $currentRequest->query->get('id_language');
		if (!isset($languageID)) {
			return;
		}
		
		$targetLangCode = $this->languageResolver->getLangCodeFromID($languageID);
		if (!isset($targetLangCode)) {
			return;
		}
		
		$currentLangCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		if ($currentLangCode == $targetLangCode) {
			return;
		}
		
		$targetLang = \Drupal::languageManager()->getLanguage($targetLangCode);
		
		$queryParams = $currentRequest->query->all();
		unset($queryParams['id_language']);
		
		$targetUrl = Url::fromRoute('<current>', $queryParams, ['language' => $targetLang]);
		
		$response = new RedirectResponse($targetUrl->toString(), 307);
		$response->send();

		\Drupal::service('page_cache_kill_switch')->trigger();
	}
	
	public function loggedOutRedirection(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET' || $this->_isAdminRoute()) {
			return;
		}
		
		$bypassLogin = $this->drupalUser->hasPermission('gv_fanatics_plus_bypass_login');
		if (!isset($activeChannel) || $bypassLogin) {
			return;
		}
		
		$isLoggedIn = $this->session->isActiveAndLogged();
		$routeName = \Drupal::routeMatch()->getRouteName();
		
		$node = \Drupal::routeMatch()->getParameter('node');
		if ($node instanceof \Drupal\node\NodeInterface) {
  			if (isset($node)) {
  				$nid = $node->id();
				
				if ($nid == '41') { // contact page
					return;
				}
				
				if ($node->hasField('field_requiere_login_my_grandski')) {
					$loginRequired = $node->get('field_requiere_login_my_grandski')->value;
					if ($node->get('field_requiere_login_my_grandski')->isEmpty() || $loginRequired[0]['value'] == 0) {
						return;
					}
				}
  			}
		}
		
		if ($routeName == "bitanube.user.login") {
			return;
		}
		
		if (!$isLoggedIn 
			&& ($routeName != 'gv_fplus_auth.reset_password_email_sent' 
				&& $routeName != 'gv_fplus_auth.email_check_form' 
				&& $routeName != 'gv_fplus_auth.basic_register_form' 
				&& $routeName != 'gv_fplus_auth.activation_email_sent_form' 
				&& $routeName != 'gv_fplus_auth.login_form'
				&& $routeName != 'gv_fplus_auth.remember_password_form' 
				&& $routeName != 'gv_fplus_auth.account_activated' 
				&& $routeName != 'gv_fplus_auth.reset_password_form'
				&& $routeName != 'gv_fanatics_plus_external_access.access'
			    && $routeName != 'gv_fanatics_plus_metrics_collector.export_metrics'
			    && $routeName != 'gv_fanatics_plus_checkout.post_payment_alias'
			    && $routeName != 'gv_fanatics_plus_order.order_detail_alias'
				&& $routeName != 'gv_fanatics_plus_utils.access_denied'
				&& $routeName != 'gv_fanatics_plus_utils.internal_error'
				&& $routeName != 'gv_fanatics_plus_utils.page_not_found')) {

			$currentUri = \Drupal::request()->getRequestUri();
			$isFrontPage = \Drupal::service('path.matcher')->isFrontPage();
			
			$queryParams = ['query' => []];
			if (!$isFrontPage && $routeName != 'gv_fanatics_plus_my_grandski.main_menu') {
				$queryParams['query']['original_url'] = $currentUri;
			}
			
			$response = new RedirectResponse(\Drupal::url('gv_fplus_auth.email_check_form', [], $queryParams), 307);
			//$response->send();
			$event->setResponse($response);
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
	}

	public function profileDefaultingRedirection(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		$bypassLogin = $this->drupalUser->hasPermission('gv_fanatics_plus_bypass_login');
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET' || $this->_isAdminRoute()) {
			return;
		}
		
		if (!$activeChannel || $bypassLogin) {
			return;
		}
		
		$userEmail = $this->session->getEmail();
		
		if (!isset($userEmail)) {
			return;
		}
		
		$profileDefaulting = $this->user->isDefaulting($userEmail);
		if ($this->session->isBoxOfficeAgent()) { // Box office agents do not go through overdue payments flow
			if ($profileDefaulting) {
				\Drupal::messenger()->addMessage($this->translationService->translate('BOX_OFFICE_AGENT_NOTICES.OVERDUE_PAYMENTS'), MessengerInterface::TYPE_WARNING);
			}
			
			return;
		}
		
		$node = \Drupal::routeMatch()->getParameter('node');
		if ($node instanceof \Drupal\node\NodeInterface) {
  			if (isset($node)) {
  				$nid = $node->id();
				if ($nid == '41') { // contact page
					return;
				}
				
				if ($node->hasField('field_requiere_login_my_grandski')) {
					$loginRequired = $node->get('field_requiere_login_my_grandski')->value;
					if ($node->get('field_requiere_login_my_grandski')->isEmpty() || $loginRequired[0]['value'] == 0) {
						return;
					}
				}
  			}
		}
		
		$routeName = \Drupal::routeMatch()->getRouteName();
		if ($profileDefaulting && 
			!($routeName == 'gv_fanatics_plus_order.order_history' 
			|| $routeName == 'gv_fanatics_plus_order.order_detail' 
			|| $routeName == 'gv_fanatics_plus_order.resolve_order_pending_payment' 
			|| $routeName == 'gv_fanatics_plus_order.order_payment_failure' 
			|| $routeName == 'gv_fanatics_plus_order.document_download'
			|| $routeName == 'gv_fanatics_plus_checkout.test'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_logalty'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_shipping_method'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_documents'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_documents_v2'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_shipping_data'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_shipping_data_complete'
			|| $routeName == 'gv_fanatics_plus_my_grandski.main_menu'
			|| $routeName == 'gv_fplus_auth.logout'
			|| $routeName == 'gv_fplus_auth.user_profile_personal_data_form'
			|| $routeName == 'gv_fplus_auth.user_profile_residence_data_form'
			|| $routeName == 'gv_fanatics_plus_ski_slopes.detail'
			|| $routeName == 'gv_fanatics_plus_ski_slopes.history'
			|| $routeName == 'gv_fanatics_plus_checkout.integrant_list'
			|| $routeName == 'gv_fanatics_plus_checkout.remove_integrant'
			|| $routeName == 'gv_fanatics_plus_invitation.invitation_list'
			|| $routeName == 'gv_fanatics_plus_invitation.use_invitation'
			|| $routeName == 'gv_fanatics_plus_metrics_collector.export_metrics'
			|| $routeName == 'gv_fanatics_plus_checkout.post_payment_alias'
			|| $routeName == 'gv_fanatics_plus_ski_slopes.history_integrant'
			|| $routeName == 'gv_fanatics_plus_ski_slopes.detail_integrant'
			|| $routeName == 'gv_fplus_auth.change_password_modal')) {
				
			if ($routeName == 'gv_fanatics_plus_checkout.form') {
				$currentCheckoutStep = \Drupal::routeMatch()->getParameter('step');
				if ($currentCheckoutStep == 'profile-data') {
					return;
				}
			}

			$destination_url = Url::fromRoute('gv_fanatics_plus_order.order_history', [], ['query' => ['defaulting' => 1]]);
			$response = new RedirectResponse($destination_url->toString(), 307);
			$response->send();
			//$event->setResponse($response);
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
	}

	public function profileIncompleteRedirection(GetResponseEvent $event) {

		$activeChannel = $this->channelResolver->resolve();
		$bypassLogin = $this->drupalUser->hasPermission('gv_fanatics_plus_bypass_login');
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		if ($method != 'GET' || $this->_isAdminRoute()) {
			return;
		}
		
		if (!$activeChannel || $bypassLogin) {
			return;
		}
		
		$userEmail = $this->session->getEmail();
		if (!isset($userEmail)) {
			return;
		}
		
		$profileDefaulting = $this->user->isDefaulting($userEmail);
		if ($profileDefaulting) {
			return;
		}
		
		if ($this->session->isManagingIntegrant()) {
			$profileDataComplete =  $this->user->isProfileDataCompleteByClientID($this->session->getActiveIntegrantClientID());
		} else {
			$profileDataComplete = $this->user->isProfileDataComplete($userEmail);
		}
		
		if ($this->session->isBoxOfficeAgent()) { // Box office agents incomplete profile flow
			if (!$profileDataComplete) {
				\Drupal::messenger()->addMessage($this->translationService->translate('BOX_OFFICE_AGENT_NOTICES.PROFILE_INCOMPLETE'), MessengerInterface::TYPE_WARNING);
			}
			
			return;
		}
		
		
		$node = \Drupal::routeMatch()->getParameter('node');
		if ($node instanceof \Drupal\node\NodeInterface) {
  			if (isset($node)) {
  				$nid = $node->id();
				if ($nid == '41') { // contact page
					return;
				}
				
				if ($node->hasField('field_requiere_login_my_grandski')) {
					$loginRequired = $node->get('field_requiere_login_my_grandski')->value;
					if ($node->get('field_requiere_login_my_grandski')->isEmpty() || $loginRequired[0]['value'] == 0) {
						return;
					}
				}
  			}
		}
		
		$routeName = \Drupal::routeMatch()->getRouteName();
		if ($routeName == 'gv_fanatics_plus_external_access.access' || $routeName == 'gv_fplus_auth.logout') {
			return;
		}
		
		$currentCheckoutStep = \Drupal::routeMatch()->getParameter('step');
		if (!$profileDataComplete && !($routeName == 'gv_fanatics_plus_checkout.form' && $currentCheckoutStep == 'profile-data')) {
			$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => 'profile-data']);
			$response = new RedirectResponse($destination_url->toString(), 307);
			
			\Drupal::messenger()->addMessage($this->translationService->translate('AUTH.REVIEW_PROFILE_REDIRECT_MESSAGE'));
			//$response->send();
			$event->setResponse($response);
			//\Drupal::messenger()->addMessage($this->t('Please review your profile before proceeding', [], ['context' => TranslationContext::PROFILE_DATA]));
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
	}

	public function homeRedirect(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		$bypassLogin = $this->drupalUser->hasPermission('gv_fanatics_plus_bypass_login');
		
		$currentRequest = \Drupal::request();
		$method = $currentRequest->getMethod();
		$isFrontPage = \Drupal::service('path.matcher')->isFrontPage();
		
		if (($method != 'GET' || $this->_isAdminRoute()) && !$isFrontPage) {
			return;
		}
		
		if (!$activeChannel || $bypassLogin) {
			return;
		}
		
		if ($isFrontPage) {
			$destination_url = Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu');
			$response = new RedirectResponse($destination_url->toString(), 307);
			$response->send();
			\Drupal::service('page_cache_kill_switch')->trigger();
		}
	}

	public function setupRouteMetricsLabels(GetResponseEvent $event) {
		$activeChannel = $this->channelResolver->resolve();
		if (!isset($activeChannel) || $this->_isAdminRoute()) {
			return;
		}
		
		$metricsCollector = $this->metricsCollector;
		$this->apiClient->setStatsSubscriber($metricsCollector);
		
		$routeOptions = \Drupal::routeMatch()->getRouteObject()->getOptions();
		if (isset($routeOptions['gv_metrics_collector_namespace'])) {
			$metricsCollector->setNamespace($routeOptions['gv_metrics_collector_namespace']);
		} else {
			$metricsCollector->setNamespace(NULL);
		}
		
		if (isset($routeOptions['gv_metrics_collector_step'])) {
			$metricsCollector->setStep($routeOptions['gv_metrics_collector_step']);
		} else {
			$metricsCollector->setStep('');
		}
		
		if (isset($routeOptions['gv_metrics_collector_action'])) {
			$metricsCollector->setAction($routeOptions['gv_metrics_collector_action']);
		} else {
			$metricsCollector->setAction('');
		}
		
		if (isset($routeOptions['gv_metrics_collector_initial_load'])) {
			$initialLoad = ($routeOptions['gv_metrics_collector_initial_load'] == 'TRUE');
			$metricsCollector->setInitialLoad($initialLoad);
		} else {
			$currentRequest = \Drupal::request();
			$method = $currentRequest->getMethod();
			if ($method != 'GET') {
				$metricsCollector->setInitialLoad(FALSE);
			} else {
				$metricsCollector->setInitialLoad(TRUE);
			}
		}
		
		$routeName = \Drupal::routeMatch()->getRouteName();
		if ($metricsCollector->getInitialLoad() && $routeName != 'gv_fanatics_plus_checkout.form') {
			$metricsCollector->incStepCounter();
		}
	}

    /**
     * {@inheritdoc}
     */
    public function alterRoutes(RouteCollection $collection) {
        if ($route_front = $collection->get('<front>')) {
            $route_front->setOption('no_cache', TRUE);
        }
    }

  	/**
   	* {@inheritdoc}
   	*/
  	public static function getSubscribedEvents() {
  		
    	$events[KernelEvents::REQUEST][] = array('setupDbmSession', 35);
		$events[KernelEvents::REQUEST][] = array('homeRedirect');
		$events[KernelEvents::REQUEST][] = array('checkForExternalLogin');
		$events[KernelEvents::REQUEST][] = array('targetLanguageRedirection');
		$events[KernelEvents::REQUEST][] = array('loggedOutRedirection');
		$events[KernelEvents::REQUEST][] = array('profileDefaultingRedirection', -3);
		$events[KernelEvents::REQUEST][] = array('profileIncompleteRedirection', -2);
		$events[KernelEvents::REQUEST][] = array('setupRouteMetricsLabels');
		
    	return $events;
  	}	
}

?>
