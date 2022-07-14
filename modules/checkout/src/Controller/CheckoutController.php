<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controlador principal del proceso de Checkout.
 * Responsable por renderizar el paso correspondiente al estado actual del carrito gestionado por la entidad CheckoutOrderManager.
 * También hace otras comprobaciones de lógica de negocio previamente a la renderización del paso.
 */
class CheckoutController extends ControllerBase {

	const UNDERAGE_FALLBACK_ROUTE = 'gv_fanatics_plus_my_grandski.main_menu';

	protected $apiClient;
	protected $session;
	protected $checkoutOrderManager;
	protected $postPaymentResolver;
	protected $formBasicValidations;
	protected $formBuilder;
	protected $user;
	protected $metricsCollector;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$checkoutOrderManager = $container->get('gv_fanatics_plus_checkout.checkout_order_manager');
		$postPaymentResolver = $container->get('gv_fanatics_plus_checkout.post_payment_resolver');
		$formBuilder = $container->get('form_builder');
		$formBasicValidations = $container->get('gv_fplus_auth.form_basic_validations');
		$user = $container->get('gv_fplus_auth.user');
		$metricsCollector = $container->get('gv_fanatics_plus_metrics_collector.metrics_collector');
		
		return new static($apiClient, $session, $checkoutOrderManager, $postPaymentResolver, $formBuilder, $formBasicValidations, $user, $metricsCollector);
	}
	
	public function __construct($apiClient, $session, $checkoutOrderManager, $postPaymentResolver, $formBuilder, $formBasicValidations, $user, $metricsCollector) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->postPaymentResolver = $postPaymentResolver;
		$this->formBuilder = $formBuilder;
		$this->formBasicValidations = $formBasicValidations;
		$this->user = $user;
		$this->metricsCollector = $metricsCollector;
	}
	
	/**
	 * Método principal de renderización de pasos del proceso de Checkout
	 */
	public function formPage(RouteMatchInterface $route_match) {

		$requested_step_id = $route_match->getParameter('step');
		$step_id = $this->checkoutOrderManager->getCheckoutStepId($requested_step_id);

		// var_dump($requested_step_id);
		// echo "<br>";
		// var_dump($step_id);
		// die();

		$minAgeForBuying = $this->formBasicValidations->minimumAgeForBuying($this->session->getIdentifier())->MinimumAgeForNewsletter;
		$IDUser = $this->session->getIDUser();
		
		if (isset($IDUser)) {
			$userProfile = $this->user->getProfileByID($IDUser);
	
			$userBirthDate = $userProfile->BirthDate;
			$now = new \DateTime();
			$ageDiff = $now->diff(new \DateTime($userBirthDate));
			$age = $ageDiff->y;
			
			if (($age < $minAgeForBuying) && ($requested_step_id != CheckoutOrderSteps::PROFILE_DATA && !$this->session->isBoxOfficeAgent())) {
				$url = Url::fromRoute(CheckoutController::UNDERAGE_FALLBACK_ROUTE);
				return new RedirectResponse($url->toString(), 307);
			}
			
			if ($requested_step_id != CheckoutOrderSteps::PROFILE_DATA && $userProfile->DataCompleted == FALSE && !$this->session->isBoxOfficeAgent()) {
				$url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA]);
     			return new RedirectResponse($url->toString(), 307);
			}
		}

		if ($requested_step_id != $step_id && $step_id != CheckoutOrderSteps::POST_PAYMENT) {
      		$url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => $step_id]);
     		return new RedirectResponse($url->toString(), 307);
    	}
		
		$this->checkoutOrderManager->setCurrentStepId($requested_step_id);
		
		$metricsCollector = $this->metricsCollector;
		
		$request = \Drupal::request();
		$subStep = $request->query->get('step');

		switch ($requested_step_id) {

			case CheckoutOrderSteps::PROFILE_DATA: {

				$metricsCollector->setStep('profile_data');
				if ($metricsCollector->getInitialLoad()) {
					$metricsCollector->incStepCounter();
				}
				if (isset($subStep) && $subStep == '2') {
					$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION]);
					$go_back_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA]);
					return $this->formBuilder->getForm(\Drupal\gv_fplus_auth\Form\Multistep\UserProfile\ResidenceDataForm::class, $destination_url->toString(), $go_back_url->toString());
				} else {
					$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PROFILE_DATA], ['query' => ['step' => '2']]);
					$form = $this->formBuilder->getForm(\Drupal\gv_fplus_auth\Form\Multistep\UserProfile\PersonalDataForm::class, $destination_url->toString());
					return $form;
				}
			}
			
//			case CheckoutOrderSteps::PRODUCT_SELECTION: {
//				$metricsCollector->setStep('select_products');
//				if ($metricsCollector->getInitialLoad()) {
//					$metricsCollector->incStepCounter();
//				}
//
//				$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PAYMENT]);
//				$form = $this->formBuilder->getForm(\Drupal\gv_fanatics_plus_checkout\Form\SelectProductForm::class, $destination_url->toString());
//				return $form;
//			}

			 case CheckoutOrderSteps::PRODUCT_SELECTION: {
			 	$metricsCollector->setStep('select_products');
			 	if ($metricsCollector->getInitialLoad()) {
			 		$metricsCollector->incStepCounter();
			 	}
				
			 	$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::DOCUMENTS]);
			 	$form = $this->formBuilder->getForm(\Drupal\gv_fanatics_plus_checkout\Form\SelectProductForm::class, $destination_url->toString());
			 	return $form;
			 }

			 case CheckoutOrderSteps::DOCUMENTS: {
			 	$metricsCollector->setStep('pending_documents');
			 	if ($metricsCollector->getInitialLoad()) {
			 		$metricsCollector->incStepCounter();
			 	}
				
			 	$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PAYMENT]);
			 	$form = $this->formBuilder->getForm(\Drupal\gv_fanatics_plus_checkout\Form\PostPayment\ShippingDocumentsFormV2::class, $destination_url->toString());
			 	return $form;
			 }
			
			
			case CheckoutOrderSteps::PAYMENT: {
				$metricsCollector->setStep('select_payment_method');
				if ($metricsCollector->getInitialLoad()) {
					$metricsCollector->incStepCounter();
				}
				
				$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::POST_PAYMENT]);
				$form = $this->formBuilder->getForm(\Drupal\gv_fanatics_plus_checkout\Form\SelectPaymentMethodForm::class, $destination_url->toString());
				return $form;
			}
		}

		$url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => $step_id]);
     	return new RedirectResponse($url->toString(), 307);
	}
	
	public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
		$requested_step_id = $route_match->getParameter('step');
		$access = AccessResult::allowedIf(TRUE);
		return $access;
	}
}

?>
