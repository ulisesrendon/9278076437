<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\CloseOffCanvasDialogCommand;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario del paso de definición de los métodos de envío / recarga y edición de foto del proceso de Post-pago.
 */
class PostPaymentShippingMethodModalForm extends FormBase {

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'post_payment_shipping_method_modal_form';
	}

	public function __construct() {}

	private function _getDescriptionMarkup() {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		$markup = $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INSTRUCTIONS');
		return $markup;
	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['#prefix'] = '<div id="member_get_member_modal">';
		$form['#suffix'] = '</div>';

		$form['details'] = [
			'#markup' => $this->_getDescriptionMarkup()
		];

		$form['actions'] = ['#type' => 'actions'];
		
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this -> t('Close', array(), array('context' => TranslationContext::CHECKOUT)), 
			'#attributes' => [ ], 
			'#ajax' => [
				'callback' => [$this, 'cancelModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fanatics_plus_checkout.close_off_canvas_dialog'),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			], 
		];

		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/member_get_member_modal_form';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitInsuranceModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		return $response;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
				
		//$response->addCommand(new RefreshCartAjaxCommand(NULL));
		$response -> addCommand(new CloseOffCanvasDialogCommand(NULL));

		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
	}

}
