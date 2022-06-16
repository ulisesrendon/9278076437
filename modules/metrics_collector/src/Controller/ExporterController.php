<?php

namespace Drupal\gv_fanatics_plus_metrics_collector\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para ruta de exportación de métricas
 */
class ExporterController {
	private $metricsCollectorService;
	
	public function __construct() {
		$this->metricsCollectorService = \Drupal::service('gv_fanatics_plus_metrics_collector.metrics_collector');
	}
	
	public function show() {
		$result = '--';
		//$result = $this->metricsCollectorService->export(); // TODO: descomentar para depurar métricas de Prometheus
		return new Response($result, 200, ['Content-type: ' . \Prometheus\RenderTextFormat::MIME_TYPE]);
	}
}
