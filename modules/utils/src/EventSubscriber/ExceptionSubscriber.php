<?php

namespace Drupal\gv_fanatics_plus_utils\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Render\MainContent\HtmlRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\gv_fanatics_plus_utils\Controller\InternalErrorController;

/**
 * Suscriptor de excepciones de servidor
 */
class ExceptionSubscriber extends HttpExceptionSubscriberBase {

	private $session;
	
	public function __construct() {
		$this->session = \Drupal::service('gv_fplus.session');
	}

    public function getHandledFormats() {
	    return ['html'];
    }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
      $controller = new InternalErrorController();
	  $content = $controller->show();
	  
	  $exception = $event->getException();
	  if (method_exists($exception, 'getResponse')) {
	  	$response = $exception->getResponse();
		if (method_exists($response, 'getStatusCode')) {
			$statusCode = $response->getStatusCode();
			if ($statusCode == 501 || $statusCode == 401) {
				$email = $this->session->getEmail();
				$this->session->logout();
				
				$currentUri = \Drupal::request()->getRequestUri();
				
				if (!isset($email)) {
					$event->setResponse(new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form', [], ['query' => ['expired' => 1, 'original_url' => $currentUri]])->toString(), 307));
				} else {
					$event->setResponse(new RedirectResponse(Url::fromRoute('gv_fplus_auth.login_form', [], ['query' => ['email' => $email, 'expired' => 1, 'original_url' => $currentUri]])->toString(), 307));
				}
				
				return;
			}
		}
	  }
	  
      $response = \Drupal::service('main_content_renderer.html')->renderResponse($content, \Drupal::request(), \Drupal::routeMatch());
      $event->setResponse($response);
  }
  
  /**
   * Specifies the priority of all listeners in this class.
   *
   * The default priority is 1, which is very low. To have listeners that have
   * a "first attempt" at handling exceptions return a higher priority.
   *
   * @return int
   *   The event priority of this subscriber.
   */
  protected static function getPriority() {
    return -255;
  }

}