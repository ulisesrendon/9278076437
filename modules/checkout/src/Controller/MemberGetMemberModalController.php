<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

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

use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador para renderizar la modal con información auxiliar (benefícios y condiciones) para la funcionalidad de MemberGetMember
 */
class MemberGetMemberModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $formBuilder;
  protected $cart;
  protected $translationService;

  public function __construct($cart, FormBuilder $formBuilder, $translationService) {
    $this->cart = $cart;
    $this->formBuilder = $formBuilder;
	$this->translationService = $translationService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gv_fanatics_plus_cart.cart'),
      $container->get('form_builder'),
      $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  public function openModalForm() {
    $response = new AjaxResponse();
	
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\MemberGetMemberModalForm');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenOffCanvasDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENTS_MODAL_HEADER'), $modal_form, ['width' => '800']));
    $response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
    
    return $response;
  }
}
