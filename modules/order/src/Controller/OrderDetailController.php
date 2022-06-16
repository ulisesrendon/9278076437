<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controlador para la ficha de detalle de un expediente
 */
class OrderDetailController extends ControllerBase {

	private $apiClient;
	private $documents;
	private $order;
	private $orderPendingData;
	private $session;
	private $channelResolver;
	
	private $dateFormatter;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$order = $container->get('gv_fanatics_plus_order.order');
		$orderPendingData = $container->get('gv_fanatics_plus_order.order_pending_data');
		$documents = $container->get('gv_fanatics_plus_order.documents');
		$dateFormatter =  $container->get('date.formatter');
		$channelResolver = $container->get('gv_fplus.channel_resolver');
		
		return new static($apiClient, $session, $order, $orderPendingData, $documents, $dateFormatter, $channelResolver);
	}
	
	public function __construct($apiClient, $session, $order, $orderPendingData, $documents, $dateFormatter, $channelResolver) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->order = $order;
		$this->orderPendingData = $orderPendingData;
		$this->documents = $documents;
		$this->dateFormatter = $dateFormatter;
		$this->channelResolver = $channelResolver;
	}

	public function orderDetail(RouteMatchInterface $route_match) {
		$orderID = $route_match->getParameter('orderID');
		
		// 1- Check if order ID is numeric
		// 2 - Load order, check if valid
		// 3 - Check if current user is the owner of the order
			
		if (!is_numeric($orderID)) {
			throw new NotFoundHttpException();
		}
		
		$order = $this->order->getFromID($orderID, TRUE, TRUE);
		if (!isset($order)) {
			throw new NotFoundHttpException();
		}
		
		$currentUserID = $this->session->getIDUser();
		if ($order->IDUser != $currentUserID) { // Not the owner
			throw new AccessDeniedHttpException();
		}
		
		$paymentInstruments = $this->apiClient->core()->getPaymentInstruments();
		$paymentInstrumentsMap = [];
		foreach ($paymentInstruments as $payment) {
			$paymentInstrumentsMap[$payment->Identifier] = $payment;
		}
		
		foreach ($order->Payments as $index => $payment) {
			$order->Payments[$index]->PaymentInstrumentData = $paymentInstrumentsMap[$payment->IDPaymentInstrument];
		}
		
		$IDSession = $this->session->getIdentifier();
		$bookingOffices = $this->apiClient->bookingHelper()->getBookingOffices($IDSession)->List;
		$bookingOfficeMap = [];
		foreach ($bookingOffices as $bookingOffice) {
			$bookingOfficeMap[$bookingOffice->Identifier] = $bookingOffice;
		}
		
		$order->BookingOfficeLabel = isset($bookingOfficeMap[$order->IDBookingOffice]) ? $bookingOfficeMap[$order->IDBookingOffice] : NULL;
		if ($order->BookingOfficeLabel == NULL && $order->IDBookingOffice == 7) {
			$order->BookingOfficeLabel = new \stdClass();
			$order->BookingOfficeLabel->Identifier = 7;
			$order->BookingOfficeLabel->BookingOffice = $this->t('Home delivery');
		}
		
		$documents = $this->documents->getFromOrderID($orderID);
		
		$orderPendingData = $order->getPendingData();

		ksm($order);

		//JMP: Se reporta un cambio de lógica. Si el forfet está impreso o recargado, la foto no caduca
		$needsAlert = FALSE;
		$recharged = false;
		$printed = false;
		$noNeedsPhoto = array();
		

		foreach ($order->Services as $serv) {
		    $recharged = $serv->SeasonPassData->Recharged;
		    $printed = $serv->SeasonPassData->Printed;
		    $notNeedsNewPhoto[$serv->Identifier] = FALSE;

			//Nueva lógica. Puede no necesitar foto, pero si tiene otros documentos pendientes debe mostrarse la alerta
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
		        $notNeedsNewPhoto[$serv->Identifier] = TRUE;
		    }
		    else {$needsAlert = TRUE;}
		}

		$request = \Drupal::request();
		$hasPayment = $request->query->get('post_payment');
		if (isset($hasPayment)) {
			\Drupal::messenger()->addMessage($this->t('The payment was performed sucessfully', [], []));
		}
		
		$orderBookingRechargeable = $order->bookingRechargeable();
		
		$showOverduePaymentsBanner = FALSE;
		$showPendingDataBanner = FALSE;
		$showPostPaymentRevisionBanner = FALSE;
		$firstOverduePayment = NULL;
		if ($order->PendingData->OverduePayment || !$order->isPaid()) {
			$firstOverduePayment = $order->getFirstOverduePayment();
			if ($firstOverduePayment != NULL) {
				$showOverduePaymentsBanner = TRUE;
			}
		} else if ($order->getPendingData()->Counter > 0 && $order->isPaid() && $order->isInitialSale() && $needsAlert) {
			$showPendingDataBanner = TRUE;
		} else if (!$order->isConsumed() && $order->isPaid() && $order->isInitialSale()) {
			$showPostPaymentRevisionBanner = TRUE;
		}
		ksm($showPendingDataBanner);
		$build = [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_order/detail'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_order_detail', 
			'#order' => $order, 
			'#documents' => $documents, 
			'#order_pending_data' => $orderPendingData,
			'#order_booking_rechargeable' => $orderBookingRechargeable,
			'#show_overdue_payments_banner' => $showOverduePaymentsBanner,
			'#show_post_payment_pending_data_banner' => $showPendingDataBanner,
			'#show_post_payment_revision_banner' => $showPostPaymentRevisionBanner,
			'#first_overdue_payment' => $firstOverduePayment,
		    '#notNeedsPhoto' => $notNeedsNewPhoto
		];
		
		$build['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$build['#attached']['library'][] = 'system/ui.dialog';
		$build['#attached']['library'][] = 'gv_fanatics_plus_checkout/change_product_ajax_commands';
		
		if (isset($hasPayment) && !$order->SynchronizedConversionScript) {
			$build['#attached']['library'][] = 'gv_fanatics_plus_checkout/checkout_complete'; 
      		$build['#attached']['drupalSettings']['checkout_complete']['order'] = $order;
			$this->order->editBookingSynchronizedConversionScript($orderID, TRUE);
		}
		
		return $build;
	}

	public function orderDetailAlias(RouteMatchInterface $route_match) {
		$bookingLocator = $route_match->getParameter('bookingLocator');
		
		if (!isset($bookingLocator)) {
		    throw new NotFoundHttpException();
		}
		
		$langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
	    $langPrefix = '';
	    if ($langCode != 'es') {
	        $langPrefix = '/' . $langCode;
	    }
		
		try{
    		$order = $this->order->getIDFromLocator($bookingLocator);
    		
    		if (!isset($order)) {
    			throw new NotFoundHttpException();
    		}
    		
    		$currentUserID = $this->session->getIDUser();
    // 		if ($order->IDUser != $currentUserID) { // Not the owner
    // 			throw new AccessDeniedHttpException();
    // 		}
    		if ($order->IDUser != $currentUserID) { // Not the owner
    		    $this->session->logout();
    		    
    		    $currentUri = \Drupal::service('path.current')->getPath();
    		    
    		    
    		    $queryParams = ['original_url' => $langPrefix . $currentUri, 'email_marketing' => '1'];
    		    
    		    $email = NULL;
    		    $baseRoute = 'gv_fplus_auth.email_check_form';
    		    if (isset($_GET['email'])) {
    		        $queryParams['email'] = $_GET['email'];
    		        $baseRoute = 'gv_fplus_auth.login_form';
    		    }
    		    
    		    $response = new RedirectResponse(\Drupal::url($baseRoute, [], ['query' => $queryParams]), 307);
    		    $response->send();
    		}
    		else{
        		$targetURL = Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $order->Identifier]);
        		$response = new RedirectResponse($targetURL->toString(), 307);
        		$response->send();		    
    		}
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
