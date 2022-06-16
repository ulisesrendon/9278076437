<?php

namespace Drupal\gv_fanatics_plus_my_grandski\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bloque de menú de MyGrandSki de Fanatics / Plus / Temporada
 *
 * @Block(
 *   id = "gv_fanatics_plus_my_grandski_menu_top",
 *   admin_label = @Translation("Menu Fanatics"),
 *   category = @Translation("GV Fanatics / Plus")
 * )
 */
class MenuFanaticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

	public function __construct(array $configuration, $plugin_id, $plugin_definition) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static($configuration, $plugin_id, $plugin_definition);
	}

	public function build() {
		$menuResolver = \Drupal::service('gv_fanatics_plus_my_grandski.menu_resolver');
		return [
			'#theme' => 'gv_fanatics_plus_my_grandski_menu_top',
			'#main_menu' => $menuResolver->resolve()
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getCacheMaxAge() {
	    return 0;
	}
}
