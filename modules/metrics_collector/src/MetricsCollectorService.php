<?php

namespace Drupal\gv_fanatics_plus_metrics_collector;

require '../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\RenderTextFormat;

use Drupal\gv_fplus_dbm_api\StatsSubscriberInterface;

/**
 * Servicio de agregación de métricas
 */
class MetricsCollectorService implements StatsSubscriberInterface {
	
	const DEFAULT_ADAPTER = 'redis';
	const DEFAULT_PROMETHEUS_DOMAIN = 'gv_fanatics_plus';
	
	private $channelResolver;
	
	private $registry;
	private $activeNamespaceName;
	private $activeStepName;
	private $activeLabels;
	private $currentAction;
	private $initialLoad;
	
	private $totalApiResponseTimes;
	
	public function __construct() {
		
		$this->channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		
		Redis::setDefaultOptions([ // TODO: set variables on config
	        'host' => '127.0.0.1',
	        'port' => 6379,
	        'password' => 'hyDracHIPtANEGenTINch',
	        'timeout' => 0.1, // in seconds
	        'read_timeout' => '10', // in seconds
	        'persistent_connections' => false
    	]);
		
    	$adapter = new Redis();
		$this->registry = new CollectorRegistry($adapter);
		
		$this->activeNamespaceName = 'unknown';
		$this->activeStepName = '';
		$this->currentAction = '';
		$this->initialLoad = TRUE;
		$this->activeLabels = ['step', 'action', 'initial_load', 'doblemente_channel_id', 'source_domain', 'api_route', 'http_status_code'];
		$this->totalApiResponseTimes = [];
	}
	
	/**
	 * Añade observaciones de métricas de prometheus.
	 * 
	 * @param $seconds número de segundos
	 * @param $addCounter TRUE para añadir una observación al contador, FALSE en caso contrario
	 * @param $extraLabels Etiquetas extra para las observaciones de prometheus
	 * @param $setNamespace Namespace para las observaciones de prometheus
	 * @param $setStepName Nombre del paso para las observaciones de prometheus
	 * @param $buckets Buckets para las observaciones de histogramas (más información: https://prometheus.io/docs/concepts/metric_types/#histogram)
	 */
	public function pushMetric(float $seconds, $addCounter = TRUE, $extraLabels = [], $setNamespace = NULL, $setStepName = NULL, $buckets = [0.05, 0.1, 0.15,0.2, 0.25, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9,  1, 1.5, 1.75, 2, 2.5, 3, 3.5, 4, 5, 6]) {
		$labels = $this->activeLabels;
		
		$namespace = $this->activeNamespaceName;
		$step = $this->activeStepName;
		
		if ($setNamespace != NULL) {
			$namespace = $setNamespace;
		}
		
		if ($setStepName != NULL) {
			$step = $setStepName;
		}
		
		if ($namespace == NULL || strlen($namespace) <= 0) {
			return; // no metric name defined, do nothing
		}
		
		$histogram = $this->registry->getOrRegisterHistogram(MetricsCollectorService::DEFAULT_PROMETHEUS_DOMAIN, $namespace, '', $labels, $buckets);
		$histogram->observe($seconds, $extraLabels);
		
		if ($addCounter) {
			$counter = $this->registry->getOrRegisterCounter(MetricsCollectorService::DEFAULT_PROMETHEUS_DOMAIN, $namespace, '', $labels);
			$counter->incBy(1, $extraLabels);
		}
	}
	
	/**
	 * Registra una observación de tiempo de respuesta de API
	 * 
	 * @param $seconds número de segundos
	 * @param $namespace Namespace para las observaciones de prometheus
	 * @param $extraLabels Etiquetas extra para las observaciones de prometheus
	 * @param $buckets Buckets para las observaciones de histogramas (más información: https://prometheus.io/docs/concepts/metric_types/#histogram)
	 */
	public function pushTotalApiResponseTimeMetric(float $seconds, $namespace, $extraLabels = [], $buckets = [0.05, 0.1, 0.15,0.2, 0.25, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9,  1, 1.5, 1.75, 2, 2.5, 3, 3.5, 4, 5, 6]) {
		$labels = ['step', 'action', 'initial_load', 'doblemente_channel_id', 'source_domain'];
		
		if ($namespace == NULL || strlen($namespace) <= 0) {
			return; // no metric name defined, do nothing
		}
		
		$histogram = $this->registry->getOrRegisterHistogram(MetricsCollectorService::DEFAULT_PROMETHEUS_DOMAIN, $namespace . '_total_api_time', '', $labels, $buckets);
		$histogram->observe($seconds, $extraLabels);
	}
	
	/**
	 * Registra una observación de tiempo de carga de página de Drupal
	 * 
	 * @param $seconds número de segundos
	 * @param $onlyInitialLoad TRUE si observación corresponde a la carga inicial de página, FALSE en caso contrario (ej, acciones de ajax posteriores)
	 * @param $buckets Buckets para las observaciones de histogramas (más información: https://prometheus.io/docs/concepts/metric_types/#histogram)
	 */
	public function pushLoadPageMetric(float $seconds, $onlyInitialLoad = TRUE, $buckets = [0.05, 0.1, 0.15,0.2, 0.25, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9,  1, 1.5, 1.75, 2, 2.5, 3, 3.5, 4, 5, 6]) {
		if ($onlyInitialLoad == TRUE && $this->initialLoad == FALSE) {
			return;
		}
		
		$namespace = $this->activeNamespaceName;
		$step = $this->activeStepName;
		
		$salesChannelID = $this->channelResolver->resolve()->dbm_id();
		$sourceDomain = \Drupal::request()->getHost();
		
		$labels = ['step', 'action', 'doblemente_channel_id', 'source_domain'];
		$labelValues = [$step, $this->currentAction, $salesChannelID, $sourceDomain];
		
		$histogram = $this->registry->getOrRegisterHistogram(MetricsCollectorService::DEFAULT_PROMETHEUS_DOMAIN, $namespace . '_page_load', '', $labels, $buckets);
		$histogram->observe($seconds, $labelValues);
	}
	
	/**
	 * Incrementa el contador de pasos de Prometheus
	 * Deprecado.
	 */
	public function incStepCounter() {
		$namespace = $this->activeNamespaceName;
		$step = $this->activeStepName;
		
		$salesChannelID = $this->channelResolver->resolve()->dbm_id();
		$sourceDomain = \Drupal::request()->getHost();
		
		$labels = ['step', 'action', 'initial_load', 'doblemente_channel_id', 'source_domain'];
		$labelValues = [$step, $this->currentAction, $this->initialLoad, $salesChannelID, $sourceDomain];
		
		$counter = $this->registry->getOrRegisterCounter(MetricsCollectorService::DEFAULT_PROMETHEUS_DOMAIN, $namespace . '_counter', '', $labels);
		$counter->incBy(1, $labelValues);
	}

	/**
	 * Añade tiempo de respusta al tiempo total de respuesta de la API
	 * 
	 * @param $transferTime tiempo de respusta a añadir
	 * @param $extraLabels etiquetas extra a añadir a la observación de Prometheus
	 */
	private function _addApiTotalResponseTime($transferTime, $extraLabels) {
		$namespace = $this->activeNamespaceName;
		$step = $this->activeStepName;
		
		if ($namespace == NULL || strlen($namespace) <= 0) {
			return; // no metric name defined, do nothing
		}
		
		$key = $namespace . '_' . implode("_", $extraLabels);
		if (isset($this->totalApiResponseTimes[$key])) {
			$this->totalApiResponseTimes[$key]['transfer_time'] += $transferTime;
		} else {
			$this->totalApiResponseTimes[$key] = ['transfer_time' => $transferTime, 'extra_labels' => $extraLabels, 'namespace' => $namespace];
		}
	}
	
	/**
	 * Retorna el tiempo total de respusta de API
	 */
	public function getApiTotalResponseTimes() {
		return $this->totalApiResponseTimes;
	}
	
	/**
	 * Agrega todos los tiempos de respuesta de API con las últimas llamadas acumuladas
	 */
	public function syncApiTotalResponseTimes() {
		foreach ($this->totalApiResponseTimes as $key => $value) {
			$transferTime = $value['transfer_time'];
			$extraLabels = $value['extra_labels'];
			$namespace = $value['namespace'];
			$this->pushTotalApiResponseTimeMetric($transferTime, $namespace, $extraLabels);
		}
	}
	
	/**
	 * Suscriptor on_stats del cliente HTTP de Guzzle
	 */
	public function onStats($stats) {
		if ($stats->hasResponse()) {
			$salesChannelID = $this->channelResolver->resolve()->dbm_id();
			$sourceDomain = \Drupal::request()->getHost();
			
			$requestUri = $stats->getRequest()->getUri()->__toString();
			$requestMethod = $stats->getRequest()->getMethod();
			
			$processedRequestUri = parse_url($requestUri, PHP_URL_PATH);
			$processedRequestUri = $requestMethod . ' ' . str_replace('/', '/', str_replace('http://', '', preg_replace('/[0-9]+/', '', $processedRequestUri)));
			
			$statusCode = $stats->getResponse()->getStatusCode();
			$transferTime = $stats->getTransferTime();
			
			$extraLabels = [$processedRequestUri];
			$statusCodeRange = substr($statusCode, 0, 1);
			if ($statusCodeRange != '1' && $statusCodeRange != '2' && $statusCodeRange != '3') {
				return;
			}
			
			if ($statusCodeRange != NULL && strlen($statusCodeRange) == 1) {
				$statusCodeRange = $statusCodeRange . 'xx';
			}
			
			$extraLabels = [$this->activeStepName, $this->currentAction, $this->initialLoad, $salesChannelID, $sourceDomain, $processedRequestUri, $statusCodeRange];
			$this->pushMetric($transferTime, FALSE, $extraLabels);
			
			$totalResponseTimesLabels = [$this->activeStepName, $this->currentAction, $this->initialLoad, $salesChannelID, $sourceDomain];
			$this->_addApiTotalResponseTime($transferTime, $totalResponseTimesLabels);
		} else {

		}
	}
	
	/**
	 * Exporta todas las métricas como documento
	 */
	public function export() {
		$renderer = new \Prometheus\RenderTextFormat();
		$result = $renderer->render($this->registry->getMetricFamilySamples());
		return $result;
	}
	
	/**
	 * Define el namespace
	 * @param $newNamespace namespace a definir
	 */
	public function setNamespace($newNamespace) {
		$this->activeNamespaceName = $newNamespace;
	}
	
	/**
	 * Retorna el namespace activo
	 */
	public function getNamespace() {
		return $this->activeNamespaceName;
	}
	
	/**
	 * Define el paso activo
	 * @param $newStep paso activo a definir
	 */
	public function setStep($newStep) {
		$this->activeStepName = $newStep;
	}
	
	/**
	 * Retorna el paso activo
	 */
	public function getStep() {
		return $this->activeStepName;
	}
	
	/**
	 * Define la acción activa
	 * @param $newAction acción a definir
	 */
	public function setAction($newAction) {
		$this->currentAction = $newAction;
	}
	
	/**
	 * Retorna la acción activa
	 */
	public function getAction() {
		return $this->currentAction;
	}
	
	/**
	 * Define las etiquetas activas
	 * @param $newLabels nuevas etiquetas activas a definir
	 */
	public function setLabels(array $newLabels) {
		$this->activeLabels = $newLabels;
	}
	
	/**
	 * Retorna las etiquetas activas
	 */
	public function getLabels() {
		return $this->activeLabels;
	}
	
	/**
	 * Añade una etiqueta activa
	 */
	public function addLabel($newLabel) {
		$this->activeLabels[] = $newLabel;
	}
	
	/**
	 * Define la flag de carga inicial
	 * @param $val TRUE para indicar si se está ejecutando carga inicial de página, FALSE en caso contrario
	 */
	public function setInitialLoad($val) {
		$this->initialLoad = $val;
	}
	
	/**
	 * Retorna la flag de ejecución inicial
	 */
	public function getInitialLoad() {
		return $this->initialLoad;
	}
}

?>