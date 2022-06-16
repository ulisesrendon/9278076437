<?php

namespace Drupal\gv_fplus\ChannelResolver;

use Drupal\gv_fplus\ChannelResolver\Models;

/**
 * Entidad que retorna el canal de venta activo.
 */
class DomainAccessChannelResolver implements ChannelResolverInterface
{
	private $activeDomainId;
	private $activeSalesChannel;
	
	public function __construct() {
		$this->activeDomainId = NULL;
		$this->activeSalesChannel = NULL;
	}
	
	/**
	 * Método responsable por retornar el canal de venta activo.
	 * @param $reset TRUE para refrescar la caché interna de canal activo, FALSE para hacer uso de la caché interna.
	 */
	public function resolve($reset = FALSE) {
		if (!$reset && $this->activeSalesChannel != NULL) {
			return $this->activeSalesChannel;
		}
		
		$domainNegotiator = \Drupal::service('domain.negotiator');
		$activeDomainId = $domainNegotiator->getActiveId();
		
		$channels = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadTree('fplus_canales_de_venta', 0, 1, true);
		
		foreach ($channels as $channel) {
			$domain = $channel->get('field_dominios')->first()->getValue()['target_id'];
			if ($domain != $activeDomainId) {
				continue;
			}
			
			$active = $channel->get('field_canal_activo')->first()->getValue()['value'];
			if (!$active) {
				continue;
			}
			
			$id = $channel->get('tid')->first()->getValue()['value'];
			$name = $channel->get('name')->first()->getValue()['value'];
			$dbm_id = $channel->get('field_identificador_doblemente')->first()->getValue()['value'];
			$testEnvironment = $channel->get('field_entorno_de_pruebas')->first()->getValue()['value'];
			$hideProductSearchFilter = $channel->get('field_ocultar_filtro_busqueda')->first();
			if ($hideProductSearchFilter != NULL) {
				$hideProductSearchFilter = $hideProductSearchFilter->getValue()['value'];
			}
			
			$isMemberGetMemberActive = $channel->get('field_membergetmember_activo')->first();
			if ($isMemberGetMemberActive != NULL) {
				$isMemberGetMemberActive = $isMemberGetMemberActive->getValue()['value'];
			}
			
			$IDServiceType = $channel->get('field_idservicetype')->first()->getValue()['value'];
			$startSearchDate = $channel->get('field_fecha_inicio_de_busqueda')->first()->getValue()['value'];
			$endSearchDate = $channel->get('field_fecha_fin_de_busqueda')->first()->getValue()['value'];
			$salesforceServiceTypeID = $channel->get('field_tipo_servicio_sf')->first();
			if ($salesforceServiceTypeID != NULL) {
				$salesforceServiceTypeID = $salesforceServiceTypeID->getValue()['value'];
			}
			
			$salesforceAccount = $channel->get('field_cuenta_salesforce')->first();
			if ($salesforceAccount != NULL) {
				$salesforceAccount = $salesforceAccount->getValue()['value'];
			}
			
			$testEnvironment = ($testEnvironment == "" || $testEnvironment == "0") ? FALSE : TRUE;
			$hideProductSearchFilter = ($hideProductSearchFilter == "" || $hideProductSearchFilter == "0") ? FALSE : TRUE;
			$isMemberGetMemberActive = ($isMemberGetMemberActive == "" || $isMemberGetMemberActive == "0") ? FALSE : TRUE;
			
			$baseUrl = $channel->get('field_url_base')->first()->getValue()['value'];
			$apiUsername = $channel->get('field_nombre_de_usuario_api')->first()->getValue()['value'];
			$apiPassword = $channel->get('field_contrasena_api')->first()->getValue()['value'];
			$apiLicense = $channel->get('field_licencia_api')->first()->getValue()['value'];
			
			$gtmCode = $channel->get('field_codigo_gtm')->first();
			if ($gtmCode != NULL) {
				$gtmCode = $gtmCode->getValue()['value'];
			}
			
			$this->activeDomainId = $activeDomainId;
			$this->activeSalesChannel = new Models\SalesChannel(
				$id, 
				$name, 
				$dbm_id, 
				$active, 
				$IDServiceType, 
				$startSearchDate, 
				$endSearchDate, 
				$salesforceServiceTypeID, 
				$salesforceAccount, 
				$hideProductSearchFilter, 
				$testEnvironment, 
				$baseUrl,
				$apiUsername,
				$apiPassword,
				$apiLicense,
				$gtmCode,
				$isMemberGetMemberActive
			);
			
			return $this->activeSalesChannel;
		}
		
		return NULL;
	}

	public function isActive($reset = FALSE) {
		$activeSalesChannel = $this->resolve($reset);
		return ($activeSalesChannel != NULL);
	}
}

?>
