<?php

namespace Drupal\gv_fanatics_plus_my_grandski\Controller;

/**
 * Controlador del menú principal MyGrandSki
 */
class MainMenuController {
	
	private $menuResolver;
	
	public function __construct() {
		$this->menuResolver = \Drupal::service('gv_fanatics_plus_my_grandski.menu_resolver');
	}
	
	public function display() {
		$targetMenuID = $this->menuResolver->resolve();
		
		return [
			'#attached' => [
				'library' => [
					'gv_fanatics_plus_my_grandski/main_menu'
				],
			], 
			'#theme' => 'gv_fanatics_plus_my_grandski_main_menu', 
			'#main_menu' => $targetMenuID
		];
	}
}

?>
