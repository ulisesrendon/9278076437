<?php

namespace Drupal\gv_fplus_auth\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador de apertura de modal de modificación de contraseña
 */
class OpenChangePasswordModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $session;
  protected $translationService;
  
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct($session, FormBuilder $formBuilder, $translationService) {
  	$this->session = $session;
    $this->formBuilder = $formBuilder;
	$this->translationService = $translationService;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fplus.session'),
      $container->get('form_builder'),
      $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalForm(RouteMatchInterface $route_match) {
	$response = new AjaxResponse();
	// Get the modal form using the form builder.
    $modalForm = $this->formBuilder->getForm('Drupal\gv_fplus_auth\Form\ProfileChangePasswordForm');
    $response->addCommand(new OpenModalDialogCommand($this->translationService->translate('CHANGE_PASSWORD_FORM.MODAL_HEADER'), $modalForm, ['width' => '800', 'dialogClass' => 'hide-close-btn']));
    return $response;
  }

}

?>