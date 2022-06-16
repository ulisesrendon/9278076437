<?php

namespace Drupal\gv_fplus_auth\Form\Multistep;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clase base para implementar formularios multipaso
 */
abstract class MultistepFormBase extends FormBase {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;
  
  protected $eventDispatcher;
  
  /**
   * @var String
   */
  private $storeKeyPrefix;
  
  private $translationService;
   
  /**
   * Constructs a \Drupal\demo\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user, $event_dispatcher, $translationService) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
	
	$this->eventDispatcher = $event_dispatcher;

    $this->store = $this->tempStoreFactory->get('multistep_data');
	
	$this->translationService = $translationService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('event_dispatcher'),
	  $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = array();
    $form['actions']['#type'] = 'actions';
	$form['actions']['#attributes']['class'][] = 'col-md-12 col-sm-12 col-xs-12';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10
    );

    return $form;
  }
  
  /**
   * Método auxiliar para establecer un prefijo del storage
   */
  protected function setStoreKeyPrefix($prefix) {
  	if (isset($prefix)) {
  		$this->storeKeyPrefix = $prefix;
  	} else {
  		$this->storeKeyPrefix = '';
  	}
  }

  /**
   * Método auxiliar para setear un valor del storage interno
   * 
   * @param $key índice clave a definir
   * @param $value valor a definir
   */
  protected function storeSet($key, $value) {
  	$keyPrefix = isset($this->storeKeyPrefix) ? $this->storeKeyPrefix : '';
  	return $this->store->set($keyPrefix . $key, $value);
  }

  /**
   * Método auxiliar para retornar un valor del storage interno.
   * 
   * @param $key clave a consultar
   */
  protected function storeGet($key) {
  	$keyPrefix = isset($this->storeKeyPrefix) ? $this->storeKeyPrefix : '';
  	return $this->store->get($keyPrefix . $key);
  }

  /**
   * Método auxiliar para borrar claves del storage interno
   * 
   * @param $keys claves a borrar
   */
  protected function deleteStoreKeys($keys = []) {
  	$keyPrefix = isset($this->storeKeyPrefix) ? $this->storeKeyPrefix : '';
    foreach ($keys as $key) {
      $this->store->delete($keyPrefix . $key);
    }
  }
}

?>

