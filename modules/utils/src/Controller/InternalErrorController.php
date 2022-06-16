<?php

namespace Drupal\gv_fanatics_plus_utils\Controller;

class InternalErrorController {
	private $contactPageLinkBuilder;
	
	public function __construct() {
		$this->contactPageLinkBuilder = \Drupal::service('gv_fanatics_plus_contact.contact_page_link_builder');
	}
	
	public function show() {
		$orderID = \Drupal::routeMatch()->getParameter('orderID');
		$contactPageUrl = $this->contactPageLinkBuilder->buildURL($orderID, TRUE);
		
		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_utils/internal_error_page'
				], 
			],
			'#theme' => 'gv_fanatics_plus_utils_internal_error_page',
			'#contact_page_url' => $contactPageUrl
		];
	}
}
