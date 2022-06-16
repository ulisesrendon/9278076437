<?php

namespace Drupal\gv_fanatics_plus_order\Form;

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

class ServiceDataAmountDetailsModalForm extends FormBase {

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'order_service_data_amount_details_modal_form';
	}

	public function __construct() {}

	private function _formatAmount($amount) {
		return number_format($amount, 2, ',', '.') . '€';
	}

	private function _getDescriptionMarkup($orderID, $serviceID) {
		$orderService = \Drupal::service('gv_fanatics_plus_order.order');
		$markup = '';
		
		$orderInfo = $orderService->getFromID($orderID, FALSE, TRUE);
		if (!isset($orderInfo) || count($orderInfo->Services) <= 0) {
			return $this->t('No data');
		}
		
		$targetServiceDataAmountDetails = NULL;
		foreach ($orderInfo->Services as $service) {
			if ($service->Identifier != $serviceID) {
				continue;
			}
			
			if (isset($service->SeasonPassData) && isset($service->SeasonPassData->AmountDetails) && count($service->SeasonPassData->AmountDetails) > 0) {
				$targetServiceDataAmountDetails = $service->SeasonPassData->AmountDetails;
			}
		}
		
		if (!isset($targetServiceDataAmountDetails) || count($targetServiceDataAmountDetails) < 0) {
			return $this->t('No data');
		}
		
		foreach ($targetServiceDataAmountDetails as $amountDetails) {
			$markup .= '<div class="service-data-amount-details-wrapper">';
			
			$day = $amountDetails->Day;
			$startTime = $amountDetails->StartTime;
			$skiDays = $amountDetails->SkiDays;
			$dailyCost = $amountDetails->DailyCost;
			$halfDayDiscount = $amountDetails->HalfDayDiscount;
			$accumulatedDaysDiscount = $amountDetails->AccumulatedDaysDiscount;
			$familyDiscount = $amountDetails->FamilyDiscount;
			$savings = $amountDetails->Savings;
			$dailyInsuranceCost = $amountDetails->DailyInsuranceCost;
			$insuranceSaving = $amountDetails->InsuranceSaving;
			$totalSavings = $amountDetails->TotalSavings;
			$finalPrice = $amountDetails->FinalPrice;
			$amountCharged = $amountDetails->AmountCharged;
			$insuranceAmountCharged = $amountDetails->InsuranceAmountCharged;
			$totalCharged = $amountDetails->TotalCharged;
			$members = $amountDetails->Members;
			$skiResort = $amountDetails->SkiResort;
			$totalDays = $amountDetails->TotalDays;
			
			$markup .= '<div class="divTableBody">';
			
			$markup .= '<div class="divTableRow bold">';
			$markup .= '<div class="label">' . $this->t('Day') . '</div>';
			$markup .= '<div class="value">' . $day . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Start time') . '</div>';
			$markup .= '<div class="value">' . $startTime . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Skied days') . '</div>';
			$markup .= '<div class="value">' . $skiDays . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Daily cost') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($dailyCost) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Half day discount') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($halfDayDiscount) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Accumulated days discount') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($accumulatedDaysDiscount) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow bold">';
			$markup .= '<div class="label">' . $this->t('Savings') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($savings) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Daily insurance cost') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($dailyInsuranceCost) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Insurance savings') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($insuranceSaving) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow highlight-row">';
			$markup .= '<div class="label">' . $this->t('Total savings') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($totalSavings) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow highlight-row">';
			$markup .= '<div class="label">' . $this->t('Final Plus+ price') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($finalPrice) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Charged amount') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($amountCharged) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Charged insurance amount') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($insuranceAmountCharged) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow highlight-row">';
			$markup .= '<div class="label">' . $this->t('Total charged amount') . '</div>';
			$markup .= '<div class="value">' . $this->_formatAmount($totalCharged) . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Integrants number') . '</div>';
			$markup .= '<div class="value">' . $members . '</div>';
			$markup .= '</div>';
			
			$markup .= '<div class="divTableRow">';
			$markup .= '<div class="label">' . $this->t('Station') . '</div>';
			$markup .= '<div class="value">' . $skiResort . '</div>';
			$markup .= '</div>';
			
			$markup .= '</div></div>';
		}

		return $markup;
	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state, $orderID = NULL, $serviceID = NULL) {
		$form['#prefix'] = '<div id="member_get_member_modal">';
		$form['#suffix'] = '</div>';

		$form['details'] = [
			'#markup' => $this->_getDescriptionMarkup($orderID, $serviceID)
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
		//$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/member_get_member_modal_form';

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
	public function validateForm(array &$form, FormStateInterface $form_state) {}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {}

}
