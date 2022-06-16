<?php

namespace Drupal\gv_fplus_auth\Form\Multistep\UserProfile;

use Drupal\gv_fplus_auth\Event\AuthEvents;
use Drupal\gv_fplus_auth\Event\ResidenceDataFormSubmitEvent;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

use Drupal\gv_fplus\TranslationContext;

class ResidenceDataForm extends \Drupal\gv_fplus_auth\Form\Multistep\MultistepFormBase
{

	const ANDORRA_COUNTRY_CODE = 5;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId()
	{
		return 'gv_fplus_auth_residence_data_form';
	}

	public function postalCodeAjaxCallback(array &$form, FormStateInterface $form_state)
	{
		$postalCode = $form_state->getValue('postal_code');

		if ($postalCode && strlen($postalCode) > 0) {
			$country = $form_state->getValue('country');

			$provinces = [];
			$cities = [];
			$locationData = $this->_getLocations($postalCode, $country);
			if ($locationData != NULL) {
				$provinces = $locationData['provinces'];
				$cities = $locationData['cities'];
			}

			$validPostalCode = TRUE;
			if (count($provinces) > 0 && count($cities) > 0) {
				$postalCodeMessageClass = 'success';
				$postalCodeMessage = $this->t('Valid postal code', [], ['context' => TranslationContext::PROFILE_DATA]);

				/*
				$form['base_address_container']['province_town_container']['province']['#options'] = $provinces;
				$form['base_address_container']['province_town_container']['city']['#options'] = $cities;
				$form['base_address_container']['province_town_container']['#prefix'] = '<div id="edit-province-town" class="">';
				*/
			} else {
				$postalCodeMessageClass = 'error';
				$postalCodeMessage = $this->t('Invalid postal code', [], ['context' => TranslationContext::PROFILE_DATA]);
				$validPostalCode = FALSE;

				//$form['base_address_container']['province_town_container']['#prefix'] = '<div id="edit-province-town" class="hidden">';
			}

			$form_state->setValue('province', NULL);
			$form_state->setValue('city', NULL);
		}

		/*$renderer = \Drupal::service('renderer');
  		$renderedField = $renderer->render($form['base_address_container']['province_town_container']);
		
  		$response = new AjaxResponse();
  		$response->addCommand(new ReplaceCommand('#edit-province-town', $renderedField));*/

		$response = new AjaxResponse();
		if (isset($postalCodeMessage)) {
			$postalCodeWrapper = '<small id="edit-postal-code--description" class="description text-muted ' . $postalCodeMessageClass . '">' . $postalCodeMessage . '</small>';
			$response->addCommand(new ReplaceCommand('#edit-postal-code--description', $postalCodeWrapper));
			if (!$validPostalCode) {
				$response->addCommand(new InvokeCommand('#edit-postal-code', 'addClass', ['error']));
			} else {
				$response->addCommand(new InvokeCommand('#edit-postal-code', 'removeClass', ['error']));
			}
		}

		return $response;
	}

	public function countryChangeAjaxCallback(array &$form, FormStateInterface $form_state)
	{
		$country = $form_state->getValue('country');
		//$form_state->setValue('postal_code', '');
		//$form_state->setRebuild();

		$renderer = \Drupal::service('renderer');
		$renderedField = $renderer->render($form['postal_code']);

		// If we want to execute AJAX commands our callback needs to return
		// an AjaxResponse object. let's create it and add our commands.
		$response = new AjaxResponse();
		// Issue a command that replaces the element #edit-output
		// with the rendered markup of the field created above.
		//$response->addCommand(new ReplaceCommand('#edit-postal-code-inner', $renderedField));
		$response->addCommand(new InvokeCommand('#edit-postal-code', 'val', ['']));
		// Show the dialog box.
		//$response->addCommand(new OpenModalDialogCommand('My title', $dialogText, ['width' => '300']));

		//$ajax_response->addCommand(new HtmlCommand('#edit-mail-visitor--description', $text));
		//$ajax_response->addCommand(new InvokeCommand('#edit-mail-visitor--description', 'css', ['color', $color]));

		// Finally return the AjaxResponse object.
		return $response;

		//return $form['postal_code'];
	}

	private function _getLocations($postalCode, $countryID)
	{
		if ($postalCode == NULL || $countryID == NULL) {
			return NULL;
		}

		$locationService = \Drupal::service('gv_fplus_auth.location');
		$locations = $locationService->getAll($postalCode, $countryID)->List;

		if (count($locations) <= 0) {
			return NULL;
		}

		$provinces = [];
		$cities = [];
		foreach ($locations as $location) {
			$provinces[$location->Province] = $location->Province; // TODO: Set ID
			$cities[$location->City] = $location->City; // TODO: Set ID
		}

		return ['cities' => $cities, 'provinces' => $provinces];
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $destination_url = NULL, $go_back_url = NULL)
	{
		$form = parent::buildForm($form, $form_state);

		$session = \Drupal::service('gv_fplus.session');
		$this->setStoreKeyPrefix($session->getIDUser());

		$dbmApi = \Drupal::service('gv_fplus_dbm_api.client');
		$location = \Drupal::service('gv_fplus_auth.location');
		$user = \Drupal::service('gv_fplus_auth.user');
		$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');

		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$languageResolver = \Drupal::service('gv_fplus.language_resolver');

		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$email = $session->getEmail();
		if (!isset($email)) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString(), 307);
		}

		$isCreatingIntegrant = $session->isCreatingIntegrant();
		$isManagingIntegrant = $session->isManagingIntegrant();
		$isIntegrantActive = $session->isIntegrantActive();

		try {
			if (!$isIntegrantActive) {
				$profile = $user->getProfile($email);
			} else {
				$activeIntegrantID = $session->getActiveIntegrantClientID();
				$activeIntegrant = NULL;

				$integrants = $integrant->listMembers($session->getIDClient())->List;
				foreach ($integrants as $integrant) {
					if ($integrant->IntegrantID == $activeIntegrantID) {
						$activeIntegrant = $integrant;
					}
				}

				$ownerProfile = $user->getProfile($email);
				$profile = $activeIntegrant;
			}
		} catch (\Exception $e) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}


		$minAgeForBuying = $formBasicValidations->minimumAgeForBuying($session->getIdentifier())->MinimumAgeForNewsletter;
		$userBirthDate = $profile->BirthDate;
		if (isset($userBirthDate)) {
			$now = new \DateTime();
			$ageDiff = $now->diff(new \DateTime($userBirthDate));
			$age = $ageDiff->y;

			$isUnderage = (($age < $minAgeForBuying) && !$isIntegrantActive);
		} else {
			$isUnderage = FALSE;
		}

		$hasProfile = $profile->DataCompleted;
		if ($isCreatingIntegrant) {
			$hasProfile = FALSE;
		} else if ($isManagingIntegrant) {
			$hasProfile = TRUE;
		}
		$this->storeSet('has_profile', $hasProfile);

		if (isset($destination_url)) {
			$this->storeSet('destination_url', $destination_url);
		}

		$countries = $location->getCountries()->List;
		$collectiveTypes = $dbmApi->core()->getCollectives();

		if (!$isIntegrantActive) {
			$canEditCountry = $formBasicValidations->canEditCountry($session->getIdentifier())->CanEdit;
		} else {
			if (!$isCreatingIntegrant) {
				$canEditCountry = $formBasicValidations->integrantCanEditCountry($session->getActiveIntegrantClientID())->CanEdit;
			} else {
				$canEditCountry = TRUE;
			}
		}

		$countryOptions = [];
		$firstCountryOptions = [];
		$countryDataOptions = [];
		$IDLanguage = $languageResolver->resolve()->id();
		foreach ($countries as $country) {
			$countryLabel = $country->Country;
			if (isset($IDLanguage) && $IDLanguage != 1) {
				foreach ($country->Translations as $translation) {
					if ($IDLanguage == $translation->IDLanguage) {
						$countryLabel = $translation->Translation;
						break;
					}
				}
			}

			if ($country->Code == 'ES' || $country->Code == 'AD' || $country->Code == 'FR' || $country->Code == 'GB') {
				$firstCountryOptions[$country->Identifier] = $countryLabel;
			} else {
				$countryOptions[$country->Identifier] = $countryLabel;
			}

			$countryDataOptions[$country->Identifier] = ['data-country-iso-code' => $country->Code];
		}

		$countryOptions = $firstCountryOptions + $countryOptions;

		$collectiveOptions = [];
		foreach ($collectiveTypes as $collectiveType) {
			$collectiveOptions[$collectiveType->Identifier] = $collectiveType->Colective;
		}

		$country = $form_state->getValue('country');
		$postalCode = $form_state->getValue('postal_code');

		$provinces = [];
		$cities = [];
		$locationData = $this->_getLocations($postalCode, $country);
		if ($locationData != NULL) {
			$provinces = $locationData['provinces'];
			$cities = $locationData['cities'];
		}

		$triggeringElement = $form_state->getTriggeringElement();
		if (isset($triggeringElement) && $triggeringElement['#name'] == 'country') {
		}

		$pageTitle = $translationService->translate('RESIDENCE_DATA_FORM.HEADER');
		if ($isIntegrantActive) {
			$pageTitle = $translationService->translate('RESIDENCE_DATA_FORM.HEADER_INTEGRANT');
		}

		$form['#prefix'] = '<div class="container">
                            <div class="title-container">
                                    <div class="radial-progress-bar">
                                        <div class="circle">
                                            <div class="fill">
                                                <div class="paso">
                                                    <div class="text">
                                                        <span class="current-step">2</span>
                                                        <span>/</span>
                                                        <span class="total-steps comp">2</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layout-and-title">
                                        <div class="text-layout-bg">' . $translationService->translate('RESIDENCE_DATA_FORM.BG_HEADER') . '</div>
                                        <h1>' . $pageTitle . '</h1>
                                    </div>
                                </div>';

		$form['base_address_container'] = [
			'#type' => 'markup',
			'#prefix' => '<div id="edit-base-address">',
			'#suffix' => '</div>'
		];

		$form['base_address_container']['country'] = array(
			'#type' => 'select',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.COUNTRY_FORM_TITLE'),
			'#default_value' => $profile->IDCountry,
			'#options' => $countryOptions,
			'#options_attributes' => $countryDataOptions,
			'#empty_option' => $translationService->translate('RESIDENCE_DATA_FORM.EMPTY_OPTION'),
			'#required' => TRUE,
			/*'#ajax' => [
                'callback' => '::countryChangeAjaxCallback', // don't forget :: when calling a class method.
               'disable-refocus' => TRUE, // Or TRUE to prevent re-focusing on the triggering element.
               'event' => 'change',
               'wrapper' => 'edit-postal-code-inner', // This element is updated with this AJAX callback.,
               'method' => 'replace',
               'progress' => [
                     'type' => 'throbber',
                     'message' => $this->t('Verifying...'),
               ],
             ],*/
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
			'#suffix' => '</div>'
		);

		if (!$canEditCountry) {
			$form['base_address_container']['country']['#attributes'] = ['readonly' => 'readonly', 'disabled' => 'disabled'];
		}

		if ($isCreatingIntegrant) {
			$form['base_address_container']['country']['#default_value'] = $ownerProfile->IDCountry;
		}

		$countriesRequirePostalCode = array_map(fn($value) => ['value' => $value], $formBasicValidations->getCountriesThatRequirePostalCode());
		$form['base_address_container']['postal_code'] = array(
			'#prefix' => '<div id="edit-postal-code-inner">',
			'#suffix' => '</div>',
			'#type' => 'textfield',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.POSTCODE_FORM_TITLE'),
			'#default_value' => $profile->PostalCode,
			'#description' => $translationService->translate('RESIDENCE_DATA_FORM.POSTCODE_FORM_DESCRIPTION'),
			/*'#ajax' => [
               'callback' => '::postalCodeAjaxCallback', // don't forget :: when calling a class method.
               //'callback' => [$this, 'myAjaxCallback'], //alternative notation
               'disable-refocus' => TRUE, // Or TRUE to prevent re-focusing on the triggering element.
               'event' => 'blur',
               'wrapper' => 'edit-base-address', // This element is updated with this AJAX callback.
               'progress' => [
                     'type' => 'throbber',
                     'message' => $this->t('Verifying...'),
               ],
             ],*/
			'#states' => [
				'visible' => [
					':input[name="country"]' => $countriesRequirePostalCode,
				],
				'required' => [
					':input[name="country"]' => $countriesRequirePostalCode,
				],
			],
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
			'#suffix' => '</div>'
		);

		if ($isCreatingIntegrant) {
			$form['base_address_container']['postal_code']['#default_value'] = $ownerProfile->PostalCode;
		}

		/*
        $form['base_address_container']['province_town_container'] = [
                '#type' => 'markup',
                '#prefix' => '<div id="edit-province-town" class="hidden">',
                '#suffix' => '</div>'
        ];

        $form['base_address_container']['province_town_container']['province'] = [
               '#type' => 'select',
              '#title' => $this->t('County'),
              '#default_value' => $this->store->get('province') ? $this->store->get('province') : NULL,
              '#options' => $provinces,
              '#empty_option' => $this->t('Select a value...'),
              '#required' => TRUE,
              '#prefix' => '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12">',
               '#suffix' => '</div>'
        ];

        $form['base_address_container']['province_town_container']['city'] = [
               '#type' => 'select',
              '#title' => $this->t('Town'),
              '#default_value' => $this->store->get('town') ? $this->store->get('town') : NULL,
              '#options' => $cities,
              '#empty_option' => $this->t('Select a value...'),
              '#required' => TRUE,
              '#prefix' => '<div class="col-md-6 col-sm-12 col-xs-12">',
               '#suffix' => '</div></div>'
        ];*/

		$form['phone_number'] = array(
			'#type' => 'tel',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.MOBILE_PHONE_FORM_TITLE'),
			'#default_value' => $profile->Phone,
			'#required' => TRUE,
			'#prefix' => '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12">',
			'#suffix' => '</div>',
			'#states' => [
				'invisible' => [
					':input[name="country"]' => ['value' => ''],
				]
			]
		);

		//ksm($this->storeGet('profile_image'));

		$form['full_phone_number'] = array(
			'#type' => 'tel',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.MOBILE_PHONE_FORM_TITLE'),
			'#default_value' => $profile->Phone,
			'#required' => TRUE,
			'#attributes' => [
				'class' => ['hidden']
			]
		);

		if ($isIntegrantActive) {
			unset($form['phone_number']['#required']);
			unset($form['full_phone_number']['#required']);
		}

		$form['census_number'] = array(
			'#type' => 'textfield',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.CENSUS_FORM_TITLE'),
			'#description' => $translationService->translate('RESIDENCE_DATA_FORM.CENSUS_FORM_DESCRIPTION'),
			'#default_value' => $profile->Census,
			'#states' => [
				'visible' => [
					':input[name="country"]' => ['value' => ResidenceDataForm::ANDORRA_COUNTRY_CODE],
				],
				'required' => [
					':input[name="country"]' => ['value' => ResidenceDataForm::ANDORRA_COUNTRY_CODE],
				],
			],
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12 census-field-container ">',
			'#suffix' => '</div>'
		);

		if ((!isset($profile->Census) || $profile->Census == '')) {
			$form['census_number']['#prefix'] = '<div class="col-md-12 col-sm-12 col-xs-12 census-field-container warning">';
		}


		/*$form['has_colective'] = [
	   	'#type' => 'radios',
	   	'#title' => $this->t('Do you belong to La Vanguardia or Racc Master collective?'),
			'#options' => array(
	   		'1' => $this->t('Yes'),
	   		'0' => $this->t('No'),
	 		),
	 		'#default_value' => '0',
	     	'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
      		'#suffix' => '</div>'
	 	];*/

		if (isset($profile->ClubID)) {
			$profile->IDClub = $profile->ClubID;
		}

		$form['collective_type'] = array(
			'#type' => 'select',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.COLLECTIVE_FORM_TITLE'),
			//'#description' => $translationService->translate('RESIDENCE_DATA_FORM.COLLECTIVE_FORM_DESCRIPTION'),
			'#default_value' => $profile->IDClub,
			'#options' => $collectiveOptions,
			'#empty_option' => $translationService->translate('RESIDENCE_DATA_FORM.COLLECTIVE_FORM_EMPTY_OPTION'),
			'#states' => [
				'invisible' => [
					':input[name="country"]' => ['value' => ResidenceDataForm::ANDORRA_COUNTRY_CODE],
				]
			],
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
			'#suffix' => '</div>'
		);

		if ($profile->IDCountry == ResidenceDataForm::ANDORRA_COUNTRY_CODE) {
			$form['collective_type']['#default_value'] = NULL;
		}

		$collectiveCodeTriggerValues = array_map(fn($value) => ['value' => $value], array_keys($collectiveOptions));
		$form['collective_code'] = array(
			'#type' => 'textfield',
			'#title' => $translationService->translate('RESIDENCE_DATA_FORM.COLLECTIVE_CODE_FORM_TITLE'),
			'#default_value' => $profile->ClubIdentification,
			'#states' => [
				'visible' => [
					':input[name="collective_type"]' => $collectiveCodeTriggerValues,
				],
				'required' => [
					':input[name="collective_type"]' => $collectiveCodeTriggerValues,
				],
			],
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
			'#suffix' => '</div>'
		);

		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$currentChannel = $channelResolver->resolve();
		$newsletterUrl = $this->t('Yes, <a href="https://www.grandvalira.com/en/consent-clause" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
		if ($currentChannel->isPlus()) {
			$newsletterUrl = $this->t('Yes, <a href="https://www.grandvalira.com/en/consent-clause" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
		} else if ($currentChannel->isTemporadaOA()) {
			$newsletterUrl = $this->t('Yes, <a href="https://www.ordinoarcalis.com/en/consent-clause-newsletter" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
		}

		$form['newsletter'] = array(
			'#type' => 'radios',
			'#title' => $translationService->translate('BASIC_REGISTER_FORM.NEWSLETTER_FORM_TITLE'),
			'#required' => TRUE,
			'#options' => [
				1 => $newsletterUrl,
				0 => $this->t('No')
			],
			'#default_value' => 0,
			'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
			'#suffix' => '</div>'
		);

		if ($profile->ReceiveInformation == TRUE || $isIntegrantActive) {
			unset($form['newsletter']);
		}
		/*
            $form['legal_consent'] = array(
                  '#type' => 'checkbox',
                   '#title' => $this->t('I have read and accept the MyGrandSki consent form'),
                   '#return_value' => 1,
                   '#default_value' => 0,
                 '#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
                  '#suffix' => '</div>'
             );*/

		$form['#cache']['contexts'][] = 'session';

		$form['actions']['go_back'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . Url::fromRoute('gv_fplus_auth.user_profile_personal_data_form')->toString() . '">'
				. $translationService->translate('RESIDENCE_DATA_FORM.GO_BACK_LINK_LABEL') . '</a>'
		];

		if (isset($go_back_url)) {
			$form['actions']['go_back']['#markup'] = '<a href="' . $go_back_url . '">'
				. $translationService->translate('RESIDENCE_DATA_FORM.GO_BACK_LINK_LABEL') . '</a>';
		}

		if (isset($destination_url)) {
			if (!$cart->hasBookingServices()) {
				$form['actions']['submit']['#value'] = $translationService->translate('RESIDENCE_DATA_FORM.START_PURCHASE_LINK_LABEL');
			} else {
				$form['actions']['submit']['#value'] = $translationService->translate('RESIDENCE_DATA_FORM.CONTINUE_PURCHASE_LINK_LABEL');
			}
		} else {
			$form['actions']['submit']['#value'] = $translationService->translate('RESIDENCE_DATA_FORM.SUBMIT_BTN_LABEL');
		}

		if ($isUnderage) {
			$form['actions']['submit']['#value'] = $translationService->translate('RESIDENCE_DATA_FORM.UPDATE_PROFILE_LABEL');
		}

		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';

		$form['#suffix'] = '</div>';

		$form['#attached']['library'][] = 'gv_fplus_auth/residence-data-form';

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		/*$legalConsent = $form_state->getValue('legal_consent');
		if (!$legalConsent) {
			$form_state->setErrorByName('legal_consent', $this->t('You must accept the terms and conditions'));
		}*/

		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$hasCollective = $form_state->getValue('has_colective');
		$collectiveTypeID = $form_state->getValue('collective_type');
		$collectiveCode = $form_state->getValue('collective_code');

		$dbmApi = \Drupal::service('gv_fplus_dbm_api.client');
		$session = \Drupal::service('gv_fplus.session');
		$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');
		$locationService = \Drupal::service('gv_fplus_auth.location');

		if (/*$hasCollective &&*/ isset($collectiveTypeID) && $collectiveTypeID != '' && $collectiveTypeID != 0 && isset($collectiveCode)) {
			$validCollectiveCode = $formBasicValidations->isValidCollectiveCode($session->getIdentifier(), $collectiveTypeID, $collectiveCode);
			if (!$validCollectiveCode->Valid) {
				$form_state->setErrorByName('collective_code', $translationService->translate('RESIDENCE_DATA_FORM.INVALID_COLLECTIVE_CODE'));
			}
		}

		$postalCode = $form_state->getValue('postal_code');
		$country = $form_state->getValue('country');

		/*$locationService = \Drupal::service('gv_fplus_auth.location');
		$validPostalCode = $formBasicValidations->isValidPostalCode($postalCode, $country);

		if (!$validPostalCode) {
			$form_state->setErrorByName('postal_code', $this->t('Invalid postal code'));
		}*/

		$currentCountry = $locationService->getCountryByID($country);
		if (!isset($currentCountry)) {
			$form_state->setErrorByName('country', $translationService->translate('RESIDENCE_DATA_FORM.INVALID_COUNTRY'));
		}

		$isIntegrantActive = $session->isIntegrantActive();

		$currentCountryCode = $currentCountry->Code;
		$phoneNumber = $form_state->getValue('full_phone_number');

		if (!$isIntegrantActive || ($isIntegrantActive && strlen($phoneNumber) > 0)) {
			$validPhoneNumber = $formBasicValidations->isValidPhoneNumber($phoneNumber, NULL);
			if (!$validPhoneNumber) {
				$form_state->setErrorByName('phone_number', $translationService->translate('RESIDENCE_DATA_FORM.INVALID_PHONE_NUMBER'));
			}
		}

		$countriesRequirePostalCode = array_map(fn($value) => ['value' => $value], $formBasicValidations->getCountriesThatRequirePostalCode());
		$countryRequiresPostalCode = FALSE;
		foreach ($countriesRequirePostalCode as $countryRequirement) {
			if ($countryRequirement['value'] == $country) {
				$countryRequiresPostalCode = TRUE;
			}
		}

		if ($countryRequiresPostalCode && strlen($postalCode) <= 0) {
			$form_state->setErrorByName('postal_code', $translationService->translate('RESIDENCE_DATA_FORM.MANDATORY_POSTAL_CODE'));
		}

		if ($country == 5) { // Andorra
			$censusNumber = $form_state->getValue('census_number');
			$regexPattern = '/\d\d\d\d\d\d[a-zA-Z]/m';

			preg_match_all($regexPattern, $censusNumber, $matches, PREG_SET_ORDER, 0);

			if (count($matches) <= 0 || strlen($censusNumber) > 7) {
				$form_state->setErrorByName('census_number', $translationService->translate('RESIDENCE_DATA_FORM.INVALID_CENSUS'));
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$hasProfile = $this->storeGet('has_profile');

		$name = NULL;
		$surname = NULL;
		$gender = NULL;
		$birthdate = NULL;
		$finalBirthdate = NULL;
		$dni = NULL;

		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		$image = \Drupal::service('gv_fplus_auth.image');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$apiIncidenceDecoder = \Drupal::service('gv_fanatics_plus_utils.api_incidence_decoder');
		$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');

		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$this->setStoreKeyPrefix($session->getIDUser());

		if (!$hasProfile) {
			$name = $this->storeGet('name');
			$surname = $this->storeGet('surname');
			$gender = $this->storeGet('gender');
			$birthdate = $this->storeGet('birthdate');
			$dni = $this->storeGet('dni');
			$integrantType = $this->storeGet('integrant_type');
			$email = $this->storeGet('email');
		}

		if (isset($birthdate)) {
			$date = new \DateTime($birthdate);
			$finalBirthdate = $date->format('Y-m-d\TH:i:s\Z');
		}

		$country = intval($form_state->getValue('country'));
		$postalCode = ($form_state->getValue('postal_code') != NULL) ? $form_state->getValue('postal_code') : ' ';
		$phoneNumber = $form_state->getValue('full_phone_number');
		$address = NULL;
		$censusNumber = $form_state->getValue('census_number');
		$city = NULL;
		$province = NULL;
		$addressNumber = NULL;
		$addressTypeID = NULL;
		$otherAdress = NULL;

		$newsletter = $form_state->getValue('newsletter');
		if (isset($newsletter) && $newsletter == 1) {
			$newsletter = TRUE;
		} else if (isset($newsletter) && $newsletter == 0) {
			$newsletter = FALSE;
		} else {
			$newsletter = NULL;
		}

		$collectiveTypeID = $form_state->getValue('collective_type');
		$collectiveCode = $form_state->getValue('collective_code');

		if (!isset($collectiveTypeID) || $collectiveTypeID == '' || $collectiveTypeID == 0 || $country == ResidenceDataForm::ANDORRA_COUNTRY_CODE) {
			$collectiveTypeID = 0;
			$collectiveCode = '';
		}

		$isCreatingIntegrant = $session->isCreatingIntegrant();
		$isManagingIntegrant = $session->isManagingIntegrant();
		$isIntegrantActive = $session->isIntegrantActive();
		$IDIntegrant = NULL;

		try {
			/*if (isset($profileImage)) {
                $profileImage = str_replace('data:image/jpeg;base64,', '', $profileImage);
                $uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg');
            }*/

			if (!$isIntegrantActive) {
				$updateResponse = $user->fanatics()->update(
					NULL,
					$session->getEmail(),
					$name,
					$surname,
					NULL,
					$dni,
					$gender,
					$finalBirthdate,
					$country,
					$postalCode,
					$city,
					$province,
					$address,
					$addressNumber,
					$addressTypeID,
					$otherAdress,
					$phoneNumber,
					NULL,
					$newsletter,
					$censusNumber,
					$collectiveTypeID,
					$collectiveCode
				);
				/*$updateResponse = $user->fanatics()->update(
                        NULL,
                        $session->getEmail(),
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        $country,
                        $postalCode,
                        $city,
                        $province,
                        $address,
                        $addressNumber,
                        $addressTypeID,
                        $otherAdress,
                        $phoneNumber,
                        NULL,
                        $newsletter,
                        $censusNumber,
                        $collectiveTypeID,
                        $collectiveCode
                );*/
			} else {
				// managing
				if ($isManagingIntegrant) {
					$response = $integrant->update(
						$session->getIdentifier(),
						$session->getActiveIntegrantClientID(),
						NULL, //$integrantType, 
						$email, //$email, 
						NULL, //$name, 
						NULL, //$surname, 
						NULL, //$surname2, 
						NULL, //$IDCard, 
						NULL, //$gender, 
						NULL, //$birthdate, 
						$country,
						$postalCode,
						$city,
						$province,
						NULL, //$provinceName, 
						NULL, //$address, 
						NULL, //$addressNumber, 
						NULL, //$IDAddressType, 
						NULL, //$addressMoreInfo, 
						$phoneNumber, //$phoneNumber, 
						NULL, //$telephoneNumber, 
						NULL, //$renewPass, 
						$censusNumber, //$census, 
						$collectiveTypeID,
						$collectiveCode
					);
				} else { // Creating
					try {
						$response = $integrant->create(
							$session->getIdentifier(),
							$integrantType,
							$email,
							$name,
							$surname,
							NULL,
							$dni,
							$gender,
							$birthdate,
							$country,
							$postalCode,
							NULL,
							NULL,
							NULL,
							NULL, //$address, 
							NULL, //$addressNumber, 
							NULL, //$IDAddressType, 
							NULL, //$addressMoreInfo, 
							$phoneNumber, //$phoneNumber, 
							NULL, //$telephoneNumber, 
							NULL, //$renewPass, 
							$censusNumber,
							$collectiveTypeID,
							$collectiveCode
						);

						$IDIntegrant = $response->ClientID;
					} catch (ClientException $e) {
						if ($e->getResponse()->getStatusCode() == 409 && FALSE) { // This check is unnecessary
							return \Drupal::messenger()->addMessage($this->t('The email address you provided already exists, please review the previous step information', [], ['context' => TranslationContext::PROFILE_DATA]), 'error');
						} else {
							throw $e;
						}
					}
				}
			}
		} catch (\Exception $e) {
			//ksm($e->getResponse()->getBody()->getContents());
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
			return \Drupal::messenger()->addMessage($translationService->translate('PERSONAL_DATA_FORM.INTERNAL_ERROR'), 'error');
		}

		try {
			if (!$hasProfile || $isCreatingIntegrant) {
				$profileImage = $this->storeGet('profile_image');
				if (isset($profileImage) && strlen($profileImage) > 0) {
					if (!$isIntegrantActive) {
						$profile = $user->getProfile($session->getEmail(), TRUE, TRUE, FALSE);
						$profileOldImage = $profile->Image;
						if ($profileImage != $profileOldImage) {
							$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg');
						}
					} else {
						//$activeIntegrantID = $session->getActiveIntegrantClientID();
						$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg', NULL, $IDIntegrant);
					}
				}
			}
		} catch (\Exception $e) {
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
			return \Drupal::messenger()->addMessage($translationService->translate('PERSONAL_DATA_FORM.INTERNAL_ERROR'), 'error');
		}


		$this->deleteStoreKeys([
			'name',
			'surname',
			'gender',
			'birthdate',
			'dni',
			'has_profile',
			'email',
			'integrant_type',
			'profile_image'
		]);

		$this->eventDispatcher->dispatch(AuthEvents::RESIDENCE_DATA_FORM_SUBMIT, new ResidenceDataFormSubmitEvent($isCreatingIntegrant, $isManagingIntegrant, $IDIntegrant));

		$final_destination = $this->storeGet('destination_url');

		$originalUrl = $session->getOriginalRedirectUrl();
		if (isset($originalUrl) && strlen($originalUrl) > 0) {
			$final_destination = $originalUrl;
			$session->deleteOriginalRedirectUrl();
		}

		if (!isset($final_destination)) {
			if ($isManagingIntegrant || $isCreatingIntegrant) {
				$form_state->setResponse(new RedirectResponse(Url::fromRoute('gv_fanatics_plus_checkout.integrant_list')->toString(), 307));
			} else {
				$form_state->setResponse(new RedirectResponse(Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString(), 307));
			}
		} else {
			$this->deleteStoreKeys(['destination_url']);
			$form_state->setResponse(new TrustedRedirectResponse($final_destination, 307));
		}

		if ($isCreatingIntegrant) {
			\Drupal::messenger()->addMessage($translationService->translate('RESIDENCE_DATA_FORM.INTEGRANT_PROFILE_CREATED'));
		} else if ($isManagingIntegrant) {
			\Drupal::messenger()->addMessage($translationService->translate('RESIDENCE_DATA_FORM.INTEGRANT_PROFILE_UPDATED'));
		} else {
			\Drupal::messenger()->addMessage($translationService->translate('RESIDENCE_DATA_FORM.PROFILE_UPDATED'));
		}
	}

}
?>
