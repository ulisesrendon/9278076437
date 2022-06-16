<?php

namespace Drupal\gv_fanatics_plus_utils;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use	Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Render\HtmlRenderer;

/**
 * Entidad que renderiza página de error interno (500)
 */
class CustomErrorHtmlSubscriber extends HttpExceptionSubscriberBase {
	public function getHandledFormats() {
		return ['html'];
	}
	public function on5xx(GetResponseForExceptionEvent $event) {
		(new Response()) -> send());
	}
	
	public function onException(GetResponseForExceptionEvent $event) {
		$exception = $event->getException();
		$build = ['#theme' => 'gv_fanatics_plus_error_500' ];
		$output = \Drupal::service('renderer')->render($build);
		$response = new Response();
		$response->setContent($output);
		$response->send();
	}
}

?>
