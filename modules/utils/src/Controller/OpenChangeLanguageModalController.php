<?php

namespace Drupal\gv_fanatics_plus_utils\Controller;

use Drupal\gv_fanatics_plus_checkout\Ajax\SetProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\EnableDisableProductFormSubmitAjaxCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Controlador de modal de cambio de idioma
 */
class OpenChangeLanguageModalController extends ControllerBase {
  use StringTranslationTrait;
  
  protected $session;
  protected $cart;
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
  public function __construct($session, $cart, FormBuilder $formBuilder, $translationService) {
  	$this->session = $session;
	$this->cart = $cart;
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
      $container->get('gv_fanatics_plus_cart.cart'),
      $container->get('form_builder'),
      $container->get('gv_fanatics_plus_translation.interface_translation')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalForm(RouteMatchInterface $route_match) {
    $response = new AjaxResponse();
	$referer = \Drupal::request()->headers->get('referer');
	
	$base_url = Request::createFromGlobals()->getSchemeAndHttpHost();
	// Getting the alias or the relative path.
	$alias = substr($referer, strlen($base_url));
	
	// Getting the node.
	$internalReferer = "internal:" . $alias;
	
	// Get the modal form using the form builder.
    $modalForm = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_utils\Form\ChangeLanguageModalForm', $internalReferer);
	
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->translationService->translate('CHANGE_LANGUAGE_MODAL.HEADER_LABEL', ['@username' => $this->session->getClientName()]), $modalForm, ['width' => '800', 'dialogClass' => 'change-language-form']));	

    return $response;
  }

}
