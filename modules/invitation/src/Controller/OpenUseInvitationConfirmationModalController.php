<?php

namespace Drupal\gv_fanatics_plus_invitation\Controller;

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
 * Controlador de la modal de confirmación de uso de invitación
 */
class OpenUseInvitationConfirmationModalController extends ControllerBase {
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
  	$orderID = $route_match->getParameter('orderID');
  	$serviceID = $route_match->getParameter('serviceID');
	
	$response = new AjaxResponse();
	// Get the modal form using the form builder.
    $modalForm = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_invitation\Form\UseInvitationConfirmationModalForm', $orderID, $serviceID);
    $response->addCommand(new OpenModalDialogCommand($this->translationService->translate('INVITATION.USE_INVITATION_MODAL_HEADER'), $modalForm, ['width' => '800']));
    return $response;
  }

}

?>