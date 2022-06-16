<?php
namespace Drupal\gv_fanatics_plus_metrics_collector\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Component\Utility\Timer;

/**
 * Suscriptor que computa el tiempo total de ejecución de página de Drupal.
 */
class MetricsCollectorSubscriber implements EventSubscriberInterface {
	private $metricsCollector;
	
	public function __construct() {
		$this->metricsCollector = \Drupal::service('gv_fanatics_plus_metrics_collector.metrics_collector');
	}

  public function terminate() {
  	$loadTimer = Timer::stop('gv_fanatics_plus_page_load_time');
	if (isset($loadTimer) && isset($loadTimer['time'])) {
		$pageTime = $loadTimer['time'];
		if ($pageTime > 0) {
			$pageTimeSeconds = $pageTime / 1000;
			$this->metricsCollector->pushLoadPageMetric($pageTimeSeconds);
		}
	}
	
	$this->metricsCollector->syncApiTotalResponseTimes();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
  	$events = [];
    $events[KernelEvents::TERMINATE][] = array('terminate');
    return $events;
  }

}

?>