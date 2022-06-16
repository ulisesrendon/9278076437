<?php

namespace Drupal\gv_fanatics_plus_order\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Drupal\gv_fanatics_plus_checkout\Ajax\AddOffCanvasDialogBackgroundCommand;

/**
 * Controlador de la modal desplegable del detalle de pasajes de un servicio de Forfait Plus+
 */
class ServiceDataAmountDetailsModalController extends ControllerBase {
  use StringTranslationTrait;
  
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;
  protected $order;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct($order, FormBuilder $formBuilder) {
    $this->order = $order;
    $this->formBuilder = $formBuilder;
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
      $container->get('gv_fanatics_plus_order.order'),
      $container->get('form_builder')
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
    $modal_form = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_order\Form\ServiceDataAmountDetailsModalForm', $orderID, $serviceID);

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Invoice summary', [], []), $modal_form, ['width' => '800']));
    $response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
    
    return $response;
  }
}
