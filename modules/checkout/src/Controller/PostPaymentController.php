<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\Url;

use Drupal\gv_fplus\TranslationContext;

 /**
 * Controlador principal del proceso de post-pago.
 * Responsable por redirigir el usuario al paso correspondiente del proceso de post-pago en función del estado del expediente.
 */
class PostPaymentController extends ControllerBase {

	private $apiClient;
	private $postPaymentResolver;
	private $session;
	private $order;
	private $postPaymentOrderManager;
	private $user;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$postPaymentResolver = $container->get('gv_fanatics_plus_checkout.post_payment_resolver');
		$order = $container->get('gv_fanatics_plus_order.order');
		$postPaymentOrderManager = $container->get('gv_fanatics_plus_checkout.post_payment_order_manager');
		$user = $container->get('gv_fplus_auth.user');
		
		return new static($apiClient, $session, $postPaymentResolver, $order, $postPaymentOrderManager, $user);
	}
	
	public function __construct($apiClient, $session, $postPaymentResolver, $order, $postPaymentOrderManager, $user) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->postPaymentResolver = $postPaymentResolver;
		$this->order = $order;
		$this->postPaymentOrderManager = $postPaymentOrderManager;
		$this->user = $user;
	}

	public function onPostPayment(RouteMatchInterface $route_match) {
		$orderID = $route_match->getParameter('orderID');
		
		$request = \Drupal::request();
		$subStep = $request->query->get('step');
		if (isset($subStep)) {
			$form = $this->postPaymentResolver->resolve($orderID, $subStep);
		} else {
			$form = $this->postPaymentResolver->resolve($orderID, $subStep);
		}
		
		if (!isset($form)) {
			throw new NotFoundHttpException();
		}
		
		return $form;
	}
	
	/**
	 * Redirige el usuario al paso correspondiente del proceso de post-pago en función del estado del expediente y otras condicionantes
	 */
	public function resolveFirstStep(RouteMatchInterface $route_match) {
		$orderID = $route_match->getParameter('orderID');
		$order = $this->order->getFromID($orderID, TRUE, TRUE);
		
		$request = \Drupal::request();
		$dbmBookingID = $request->query->get('dbmbookingid');
		$dbmSessionID = $request->query->get('dbmsessionid');
		
		$showPaymentSuccessMessage = 0;
		if (($dbmSessionID != NULL && strlen($dbmSessionID) > 0) || ($dbmBookingID != NULL && strlen($dbmBookingID) > 0)) {
			$showPaymentSuccessMessage = 1;
		}
		
		$withLogalty = FALSE;
		$pendingData = $order->getPendingData();
		if ($order->SignatureRequired && $pendingData->PendingSignature) {
			$withLogalty = TRUE;
			$targetURL = Url::fromRoute('gv_fanatics_plus_checkout.post_payment_logalty', ['orderID' => $orderID], ['query' => ['show_payment_success' => $showPaymentSuccessMessage]]);
		} else if ($order->hasPendingDocuments() && !$order->hasPendingShippingMethod()) {
			$targetURL = Url::fromRoute('gv_fanatics_plus_checkout.post_payment_documents', ['orderID' => $orderID]);
		} else {
			$targetURL = Url::fromRoute('gv_fanatics_plus_checkout.post_payment_shipping_method', ['orderID' => $orderID], ['query' => ['show_payment_success' => $showPaymentSuccessMessage]]);
		}
		
		$this->postPaymentOrderManager->initSteps();
		
		return new RedirectResponse($targetURL->toString(), 307);
	}

	/**
	 * Método auxiliar utilizado para las campañas de newsletter de Marketing para incentivar la recarga de forfaits.
	 */
	public function resolveFromBookingLocator(RouteMatchInterface $route_match) {
		$bookingLocator = $route_match->getParameter('bookingLocator');
		if (!isset($bookingLocator)) {
			throw new NotFoundHttpException();
		}
		
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
		$langPrefix = '';
		if ($langCode != 'es') {
			$langPrefix = '/' . $langCode;
		}
		
		try {
			$order = $this->order->getIDFromLocator($bookingLocator);
			if (!isset($order)) {
				throw new NotFoundHttpException();
			}
			
			$currentUserID = $this->session->getIDUser();
			if ($order->IDUser != $currentUserID) { // Not the owner
				$this->session->logout();
			
				$currentUri = \Drupal::service('path.current')->getPath();
				
				$queryParams = ['original_url' => $langPrefix . $currentUri, 'email_marketing' => '1'];
	
				$email = NULL;
				$baseRoute = 'gv_fplus_auth.email_check_form';
				$userProfile = $this->user->getProfileByID($order->IDUser);
				if (isset($userProfile) && isset($userProfile->Email)) {
					$email = $userProfile->Email;
				}
				
				if (isset($email)) {
					$queryParams['email'] = $email;
					$baseRoute = 'gv_fplus_auth.login_form';
				}
				
				$response = new RedirectResponse(\Drupal::url($baseRoute, [], ['query' => $queryParams]), 307);
				return $response;
			}
			
			$userEmail = $this->session->getEmail();
			if (isset($userEmail)) {
				$profileDataComplete = $this->user->isProfileDataComplete($userEmail);
				if (!$profileDataComplete) {
					$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => 'profile-data']);
					$response = new RedirectResponse($destination_url->toString(TRUE)->getGeneratedUrl(), 307);
					
					\Drupal::messenger()->addMessage($this->t('Please review your profile before proceeding', [], ['context' => TranslationContext::PROFILE_DATA]));
					return $response;
				}
			}
			
			$targetURL = Url::fromRoute('gv_fanatics_plus_checkout.post_payment', ['orderID' => $order->Identifier]);
			$response = new RedirectResponse($targetURL->toString(), 307);
			
			$this->session->deleteOriginalRedirectUrl();
			return $response;
		} catch(\Exception $e) {
			if (method_exists($e, 'getResponse')) {
	  			$response = $e->getResponse();
				if (method_exists($response, 'getStatusCode')) {
					$statusCode = $response->getStatusCode();
					if ($statusCode == 401) {
						$currentUri = \Drupal::service('path.current')->getPath();
						$queryParams = ['original_url' => $langPrefix . $currentUri, 'email_marketing' => '1'];
			
						$email = \Drupal::request()->query->get('email');
						$baseRoute = 'gv_fplus_auth.email_check_form';
						
						if (isset($email) && strlen($email) > 0) {
							$queryParams['email'] = $email;
							$baseRoute = 'gv_fplus_auth.login_form';
						}
						$response = new RedirectResponse(\Drupal::url($baseRoute, [], ['query' => $queryParams]), 307);
						return $response;
					} else {
						throw $e;
					}
				} else {
					throw $e;
				}
			} else {
				throw $e;
			}
		}
	}
}

?>
