<?php

namespace Drupal\gv_fplus_auth\Form\Multistep\UserProfile;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Form\FormStateInterface;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;

use Drupal\gv_fanatics_plus_checkout\Ajax\DisableFullscreenLoader;
use Drupal\gv_fplus\TranslationContext;

class PersonalDataForm extends \Drupal\gv_fplus_auth\Form\Multistep\MultistepFormBase
{

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId()
	{
		return 'gv_fplus_auth_personal_data_form';
	}

	public function updateImageAjaxCallback(array &$form, FormStateInterface $form_state)
	{
		$session = \Drupal::service('gv_fplus.session');
		$image = \Drupal::service('gv_fplus_auth.image');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$user = \Drupal::service('gv_fplus_auth.user');

		$profileImage = $form_state->getValue('image_base64');

		$isIntegrantActive = $session->isIntegrantActive();
		$isManagingIntegrant = $session->isManagingIntegrant();

		try {
			if (isset($profileImage)) {
				$profileImage = str_replace('data:image/jpeg;base64,', '', $profileImage);
				if (!$isIntegrantActive) {
					$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg');
				} else if ($isManagingIntegrant) {
					$activeIntegrantID = $session->getActiveIntegrantClientID();
					$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg', NULL, $activeIntegrantID);
				}
			}
		} catch (Exception $e) {
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
			\Drupal::messenger()->addMessage($this->t('Error updating profile image: an internal error ocurred', [], ['context' => TranslationContext::PROFILE_DATA]), 'error');
		}

		$response = new AjaxResponse();

		$response->addCommand(new DisableFullscreenLoader(NULL));

		try {
			if (!$isIntegrantActive) {
				$profile = $user->getProfile($email, TRUE, TRUE, FALSE);

				$profileImage = new \stdClass();
				$profileImage->ImageBase64 = $profile->Image;
				$profileImage->Expired = $profile->ImageExpired;
				$profileImage->CanEdit = $profile->CanEditImage;
			} else if ($isManagingIntegrant) {
				$activeIntegrantID = $session->getActiveIntegrantClientID();
				$activeIntegrant = NULL;

				// TODO: we shouldn't need to filter for the integrant
				$integrants = $integrant->listMember($session->getIDClient(), TRUE, FALSE)->List;
				foreach ($integrants as $integrant) {
					if ($integrant->IntegrantID == $activeIntegrantID) {
						$activeIntegrant = $integrant;
					}
				}

				$profile = $activeIntegrant;

				$profileImage = new \stdClass();
				$profileImage->ImageBase64 = $profile->ImageBase64;
				$profileImage->Expired = $profile->ImageExpired;
				$profileImage->CanEdit = $profile->ImageCanEdit;
			}

			if (isset($profileImage) && $profileImage->CanEdit == FALSE) {
				$response->addCommand(new RemoveCommand('.edit-image-btn'));
			}
		} catch (\Exception $e) {
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
			return $response;
		}


		return $response;
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $destination_url = NULL)
	{

		try {

			$form = parent::buildForm($form, $form_state);

			\Drupal::messenger()->addMessage('');

			$session = \Drupal::service('gv_fplus.session');
			$user = \Drupal::service('gv_fplus_auth.user');
			$image = \Drupal::service('gv_fplus_auth.image');
			$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');
			$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

			$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');

			$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
			$integrantTypes = $apiClient->core()->getIntegrantTypes();

			$this->setStoreKeyPrefix($session->getIDUser());

			$email = $session->getEmail();
			if (!isset($email)) {
				return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
			}

			$isCreatingIntegrant = $session->isCreatingIntegrant();
			$isManagingIntegrant = $session->isManagingIntegrant();
			$isIntegrantActive = $session->isIntegrantActive();

			$profile = NULL;
			$profileImage = NULL;
			try {
				if (!$isIntegrantActive) {
					$profile = $user->getProfile($email, TRUE, TRUE);

					$profileImage = new \stdClass();
					$profileImage->ImageBase64 = $profile->Image;
					$profileImage->Expired = $profile->ImageExpired;
					$profileImage->CanEdit = $profile->CanEditImage;
				} else if ($isManagingIntegrant) {
					$activeIntegrantID = $session->getActiveIntegrantClientID();
					$activeIntegrant = NULL;

					// TODO: we shouldn't need to filter for the integrant
					$integrants = $integrant->listMember($session->getIDClient(), TRUE)->List;
					foreach ($integrants as $integrant) {
						if ($integrant->IntegrantID == $activeIntegrantID) {
							$activeIntegrant = $integrant;
						}
					}

					$profile = $activeIntegrant;

					$profileImage = new \stdClass();
					$profileImage->ImageBase64 = $profile->ImageBase64;
					$profileImage->Expired = $profile->ImageExpired;
					$profileImage->CanEdit = $profile->ImageCanEdit;
				}
			} catch (\Exception $e) {
				throw $e;
				//return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
			}

			//if (isset($profile->Name) || isset($profile->Surname) || isset($profile->Phone) || isset($profile->Sex))
			$hasProfile = $profile->DataCompleted; // && ;
			//$hasProfile = TRUE;
			if ($isCreatingIntegrant) {
				$hasProfile = FALSE;
			} else if ($isManagingIntegrant) {
				$hasProfile = TRUE;
			}

			$this->storeSet('has_profile', $hasProfile);
			if (isset($destination_url)) {
				$this->storeSet('destination_url', $destination_url);
			}

			if (!$isIntegrantActive) {
				$canEditBirthDate = $formBasicValidations->canEditBirthDate($session->getIdentifier())->CanEdit;
			} else if ($isManagingIntegrant) {
				$canEditBirthDate = $formBasicValidations->integrantCanEditBirthDate($profile->IntegrantID)->CanEdit;
			} else {
				$canEditBirthDate = TRUE;
			}

			$canEditProfileImage = TRUE;
			$expiredProfileImage = FALSE;
			if (isset($profileImage)) {
				$canEditProfileImage = $profileImage->CanEdit;
				$expiredProfileImage = $profileImage->Expired;
			}

			$pageTitle = $translationService->translate('PERSONAL_DATA_FORM.HEADER');
			if ($isIntegrantActive) {
				//$pageTitle = $this->t('Integrant data', [], ['context' => TranslationContext::PROFILE_DATA]);
				$pageTitle = $translationService->translate('PERSONAL_DATA_FORM.HEADER_INTEGRANT');
			}

			$form['#prefix'] = '<div class="container">
								<div class="title-container">
									<div class="radial-progress-bar">
										<div class="circle">
											<div class="fill">
												<div class="paso">
													<div class="text">
														<span class="current-step">1</span>
														<span>/</span>
														<span class="total-steps">2</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="layout-and-title">
										<div class="text-layout-bg">' . $translationService->translate('PERSONAL_DATA_FORM.BG_HEADER') . '</div>
										<h1>' . $pageTitle . '</h1>
									</div>
								</div>';

			if ($isIntegrantActive) {
				$defaultProfileEmail = $profile->Email;
				if (!$hasProfile && $this->storeGet('email') != NULL) {
					$defaultProfileEmail = $this->storeGet('email');
				}

				if (!\Drupal::service('email.validator')->isValid($defaultProfileEmail)) {
					$defaultProfileEmail = NULL;
				}

				$form['integrant_email'] = [
					'#type' => 'email',
					'#title' => $translationService->translate('PERSONAL_DATA_FORM.EMAIL_FORM_TITLE'),
					'#default_value' => $defaultProfileEmail,
					'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
					'#suffix' => '</div>',
				];

				if (!isset($defaultProfileEmail) || strlen($defaultProfileEmail) <= 0) {
					$form['integrant_email_notice'] = [
						'#prefix' => '<div id="integrant-email-notice-container" class="col-md-12 col-sm-12 col-xs-12"',
						'#markup' => '<div id="integrant-email-notice" class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>'
							. $translationService->translate('PERSONAL_DATA_FORM.EMAIL_FORM_WARNING')/*$this->t('*Optional. Insert your email address to get updated about the latest news', [], ['context' => TranslationContext::PROFILE_DATA])*/ . '</p></div></div></div>',
						'#suffix' => '</div>'
					];
				}
			}

			$form['webcam_container'] = [
				'#type' => 'markup',
			];

			$defaultImg = isset($profileImage->ImageBase64) ? 'data:image/jpeg;base64,' . $profileImage->ImageBase64 : '';
			if ($isCreatingIntegrant || !$hasProfile) {
				$storedImg = $this->storeGet('profile_image');
				if (isset($storedImg) && strlen($storedImg) > 0) {
					$defaultImg = 'data:image/jpeg;base64,' . $storedImg;
				}
			}

			$editPasswordMarkup =
				'<div class="change-password-container"><div class="current-email"><span>' . $session->getEmail() . '</span></div><div class="change-password"><a class="use-ajax" href="' . Url::fromRoute('gv_fplus_auth.change_password_modal')->toString() . '">Editar contraseña</a></div></div>';
			if ($isIntegrantActive) {
				$editPasswordMarkup = '';
			}

			$defaultPreviewUrl = ($defaultImg != '') ? ($defaultImg) : 'https://via.placeholder.com/134x164';
			$form['webcam_container']['image_preview'] = [
				'#type' => 'markup',
				'#markup' => '<div class="row"><div class="col-md-12 image-preview-wrapper"><div class="image-preview-container"><img class="image-preview" data-src="' . $defaultPreviewUrl . '" /></div><a class="edit-image-btn"><span class="edit-image-btn--inner"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/pencil-fill.svg"/></span></a>' . $editPasswordMarkup . '</div></div>'
			];

			if (!$canEditProfileImage) {
				$form['webcam_container']['image_preview']['#markup'] = '<div class="row"><div class="col-md-12 image-preview-wrapper"><div class="image-preview-container"><img class="image-preview" data-src="' . $defaultPreviewUrl . '" /></div>' . $editPasswordMarkup . '</div></div>';
			}

			if ($expiredProfileImage) {
				$form['webcam_container']['image_preview']['#markup'] .= '<div class="panel warning image-expired"><div class="panel--inner"><div class="panel-heading"><p>'
					. $translationService->translate('PERSONAL_DATA_FORM.IMAGE_EXPIRED_WARNING') /*$this->t('Your profile image has expired, please upload a new one.', [], [])*/ . '</p></div></div></div>';
			}

			$form['webcam_container']['image'] = [
				'#type' => 'textfield',
				'#attributes' => [
					'class' => ['hidden']
				],
				'#maxlength' => 100000,
				'#default_value' => $defaultImg
			];

			$form['image_base64'] = [
				'#type' => 'textarea',
				'#attributes' => [
					'class' => ['hidden']
				],
				'#default_value' => $defaultImg,
				'#ajax' => [
					'callback' => '::updateImageAjaxCallback', // don't forget :: when calling a class method.
					//'callback' => [$this, 'myAjaxCallback'], //alternative notation
					'disable-refocus' => TRUE, // Or TRUE to prevent re-focusing on the triggering element.
					'event' => 'change',
					//'wrapper' => 'edit-base-address', // This element is updated with this AJAX callback.
					'progress' => [
						'type' => 'throbber',
						'message' => $translationService->translate('PERSONAL_DATA_FORM.IMAGE_UPLOADING_LABEL'),
					],
				],
			];

			if ($expiredProfileImage && $canEditProfileImage) {
				//$form['webcam_container']['image']['#required'] = TRUE;
				//$form['image_base64']['#required'] = TRUE;
			}

			$defaultProfileName = $profile->Name;
			if (!$hasProfile && $this->storeGet('name') != NULL) {
				$defaultProfileName = $this->storeGet('name');
			}
			$form['name'] = array(
				'#type' => 'textfield',
				'#title' => $translationService->translate('PERSONAL_DATA_FORM.FIRST_NAME_FORM_TITLE'),
				'#default_value' => $defaultProfileName,
				'#required' => TRUE,
				'#maxlength' => 32,
				'#prefix' => '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12">',
				'#suffix' => '</div>'
			);

			$defaultProfileSurname = $profile->Surname;
			if (!$hasProfile && $this->storeGet('surname') != NULL) {
				$defaultProfileSurname = $this->storeGet('surname');
			}

			$form['surname'] = array(
				'#type' => 'textfield',
				'#title' => $translationService->translate('PERSONAL_DATA_FORM.SURNAME_FORM_TITLE'),
				'#default_value' => $defaultProfileSurname,
				'#required' => TRUE,
				'#maxlength' => 32,
				'#prefix' => '<div class="col-md-6 col-sm-12 col-xs-12">',
				'#suffix' => '</div></div>'
			);

			$defaultGender = $profile->Sex;
			if ($defaultGender === FALSE) {
				$defaultGender = 'H';
			} else if ($defaultGender === TRUE) {
				$defaultGender = 'M';
			}

			if (!$hasProfile && $this->storeGet('gender') != NULL) {
				$defaultGender = $this->storeGet('gender');
			}

			$form['gender'] = array(
				'#type' => 'radios',
				'#title' => $translationService->translate('PERSONAL_DATA_FORM.GENDER_FORM_TITLE'),
				'#default_value' => $defaultGender,
				'#options' => array(
					'H' => $translationService->translate('PERSONAL_DATA_FORM.GENDER_OPTIONS.MALE'),
					'M' => $translationService->translate('PERSONAL_DATA_FORM.GENDER_OPTIONS.FEMALE'),
				),
				'#required' => TRUE,
				'#prefix' => '<div class="row"><div class="col-md-6 col-sm-12 col-xs-12">',
				'#suffix' => '</div>'
			);

			$defaultBirthdate = $profile->BirthDate;
			if (!$hasProfile && $this->storeGet('birthdate') != NULL) {
				$defaultBirthdate = $this->storeGet('birthdate');
			} else if (isset($defaultBirthdate)) {
				$defaultBirthdate = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $defaultBirthdate); //'15-Feb-2009');
				$defaultBirthdate = $defaultBirthdate->format('Y-m-d');
			}

			$maxBirthdateTime = strtotime("-16 year", time());
			$maxBirthdate = date("Y-m-d", $maxBirthdateTime);
			$minBirthdate = date('Y-m-d', strtotime('01-01-1900'));
			$minAgeForBuying = $formBasicValidations->minimumAgeForBuying($session->getIdentifier())->MinimumAgeForNewsletter;
			$form['birthdate'] = array(
				'#type' => 'date',
				'#title' => $translationService->translate('PERSONAL_DATA_FORM.BIRTHDATE_FORM_TITLE'),
				'#required' => TRUE,
				'#prefix' => '<div class="col-md-6 col-sm-12 col-xs-12">',
				'#suffix' => '</div>',
				'#max' => $maxBirthdate,
				'#min' => $minBirthdate,
				'#attributes' => [
					'type' => 'date',
					'max' => $maxBirthdate,
					'min' => $minBirthdate,
					'data-min-age-buying' => $minAgeForBuying
				]
			);

			if ($defaultBirthdate != "0001-01-01") {
				$form['birthdate']['#default_value'] = $defaultBirthdate;
			}

			if (!$canEditBirthDate) {
				$form['birthdate']['#attributes'] = ['readonly' => 'readonly', 'disabled' => 'disabled'];
			}

			if ($isIntegrantActive) {
				unset($form['birthdate']['#attributes']['data-min-age-buying']);

				$maxBirthdateTime = strtotime("-0 day", time());
				$maxBirthdate = date("Y-m-d", $maxBirthdateTime);
				$form['birthdate']['#max'] = $maxBirthdate;
				$form['birthdate']['#attributes']['max'] = $maxBirthdate;
			}

			$form['birthdate_under_16_notice'] = [
				'#prefix' => '<div id="profile-underage-warning-container" class="hidden col-md-6 col-sm-12 col-xs-12 offset-md-6"',
				'#markup' => '<div id="profile-underage-warning" class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>'
					. $translationService->translate('PERSONAL_DATA_FORM.UNDERAGE_NOTICE', ['@min_age_for_buying' => $minAgeForBuying])
					. /*$this->t('For legal reasons, children under @min_age_for_buying cannot make an online purchase. Upon completion of registration, you will be redirected to your profile on MyGrandSki.', ['@min_age_for_buying' => $minAgeForBuying], ['context' => TranslationContext::PROFILE_DATA])*/
					'</p></div></div></div>',
				'#suffix' => '</div>'
			];

			$defaultProfileDni = $profile->IDCard;
			if (!$hasProfile && $this->storeGet('dni') != NULL) {
				$defaultProfileDni = $this->storeGet('dni');
			}

			$form['dni'] = array(
				'#type' => 'textfield',
				'#title' => $translationService->translate('PERSONAL_DATA_FORM.PASSPORT_FORM_TITLE'),
				'#default_value' => $defaultProfileDni,
				'#required' => TRUE,
				'#prefix' => '<div class="col-md-6 col-sm-12 col-xs-12">',
				'#suffix' => '</div>'
			);

			if ($isIntegrantActive) {
				unset($form['dni']['#required']);
			}

			if ($isCreatingIntegrant) {
				$form['integrant_type'] = [
					'#type' => 'select',
					'#options' => [
						'2' => $translationService->translate('PERSONAL_DATA_FORM.INTEGRANT_TYPE.FAMILY'),
						'3' => $translationService->translate('PERSONAL_DATA_FORM.INTEGRANT_TYPE.FRIENDS')
					],
					'#empty_option' => $translationService->translate('PERSONAL_DATA_FORM.INTEGRANT_TYPE.NONE'),
					'#title' => $translationService->translate('PERSONAL_DATA_FORM.INTEGRANT_TYPE_FORM_TITLE'),
					'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
					'#suffix' => '</div>',
					'#required' => TRUE,
					'#default_value' => $this->storeGet('integrant_type')
				];
			}

			$form['#cache']['contexts'][] = 'session';

			if ($isCreatingIntegrant) {

				$goBackUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => 'select-products'])->toString();
				if (!isset($destination_url)) {
					$goBackUrl = Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString();
				}

				$form['actions']['go_back'] = [
					'#type' => 'markup',
					'#markup' => '<a href="' . Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => 'select-products'])->toString() . '">'
						. $translationService->translate('PERSONAL_DATA_FORM.GO_BACK_LINK_LABEL') . '</a>'
				];

				$form['actions']['#attributes']['class'][] = 'multiple-actions';
			}

			$form['actions']['submit']['#value'] = $translationService->translate('PERSONAL_DATA_FORM.SUBMIT_BTN_LABEL');
			$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
			$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';

			$form['#suffix'] = '</div>';

			$form['#attached']['drupalSettings']['gv_fanatics_plus_translation_context'] = $translationService->_resolveTranslationContext();
			$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
			$form['#attached']['library'][] = 'system/ui.dialog';
			$form['#attached']['library'][] = 'gv_fplus_auth/cropper-js';
			$form['#attached']['library'][] = 'gv_fplus_auth/webcam-easy';
			$form['#attached']['library'][] = 'gv_fplus_auth/personal-data-form-js';

			if (!$isManagingIntegrant && !$isCreatingIntegrant) {
				$form['#attached']['library'][] = 'gv_fplus_auth/profile-underage-notice';
			}

			//return $form;
			//JMP: unificamos lo que antes era un paso siguiente en el multistep *************************

			$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
			$languageResolver = \Drupal::service('gv_fplus.language_resolver');
			$location = \Drupal::service('gv_fplus_auth.location');
			$countries = $location->getCountries()->List;
			$dbmApi = \Drupal::service('gv_fplus_dbm_api.client');
			$user = \Drupal::service('gv_fplus_auth.user');
			$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');
			$collectiveOptions = [];
			$collectiveTypes = $dbmApi->core()->getCollectives();
			foreach ($collectiveTypes as $collectiveType) {
				$collectiveOptions[$collectiveType->Identifier] = $collectiveType->Colective;
			}

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
			$IDLanguage = $languageResolver->resolve()->id();


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
				if ($cart == NULL || !$cart->hasBookingServices()) {
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

			//FIN JMP


			return $form;

		} catch (Exception $e) {
			echo 'Excepción capturada: ', $e->getMessage(), "\n";
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state)
	{

		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		//ksm($maxBirthdateTime);
		//ksm($minBirthdateTime->getTimestamp());
		$session = \Drupal::service('gv_fplus.session');
		//$isCreatingIntegrant = $session->isCreatingIntegrant();
		//$isManagingIntegrant = $session->isManagingIntegrant();
		$isIntegrantActive = $session->isIntegrantActive();

		$birthdate = $form_state->getValue('birthdate');
		$birthdate = new \DateTime($birthdate);
		$maxBirthdateTime = strtotime("-16 year", time());
		$minBirthdateTime = \DateTime::createFromFormat('d M Y', '1 Jan 1900');

		if ($isIntegrantActive) {
			$maxBirthdateTime = strtotime("-0 day", time());
		}

		if (!$isIntegrantActive && $birthdate->getTimestamp() > $maxBirthdateTime) {
			$form_state->setErrorByName('birthdate', $translationService->translate('PERSONAL_DATA_FORM.INVALID_BIRTHDATE_UNDERAGE_PARAMETER_ERROR'));
		} else if ($isIntegrantActive && $birthdate->getTimestamp() > $maxBirthdateTime) {
			$form_state->setErrorByName('birthdate', $translationService->translate('PERSONAL_DATA_FORM.INVALID_BIRTHDATE_GENERAL_ERROR'));
		}

		if ($birthdate < $minBirthdateTime) {
			$form_state->setErrorByName('birthdate', $translationService->translate('PERSONAL_DATA_FORM.INVALID_BIRTHDATE_UNDERAGE_PARAMETER_ERROR', ['@minBirthDate' => $minBirthdateTime->format('d/m/Y')]));
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$name = $form_state->getValue('name');
		$surname = $form_state->getValue('surname');
		$gender = $form_state->getValue('gender');

		$birthdate = $form_state->getValue('birthdate');
		$date = new \DateTime($birthdate);
		$finalBirthdate = $date->format('Y-m-d\TH:i:s\Z');

		$dni = $form_state->getValue('dni');
		$profileImage = $form_state->getValue('image_base64');
		$session = \Drupal::service('gv_fplus.session');
		$image = \Drupal::service('gv_fplus_auth.image');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$apiIncidenceDecoder = \Drupal::service('gv_fanatics_plus_utils.api_incidence_decoder');

		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$this->setStoreKeyPrefix($session->getIDUser());

		$isCreatingIntegrant = $session->isCreatingIntegrant();
		$isManagingIntegrant = $session->isManagingIntegrant();
		$isIntegrantActive = $session->isIntegrantActive();

		$integrantEmail = $form_state->getValue('integrant_email');
		$integrantType = $form_state->getValue('integrant_type');

		if ($isIntegrantActive && !$isCreatingIntegrant) {
			$activeIntegrantID = $session->getActiveIntegrantClientID();
			$integrants = $integrant->listMembers($session->getIDClient(), TRUE)->List;
			foreach ($integrants as $integrant) {
				if ($integrant->IntegrantID == $activeIntegrantID) {
					$activeIntegrant = $integrant;
				}
			}

			$profile = $activeIntegrant;
			if ($integrantEmail == $profile->Email) { // TODO: remove this case, pending DBM release for this effect
				$integrantEmail = NULL;
			}
		}

		$hasProfile = $this->storeGet('has_profile');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		try {
			if (isset($profileImage) && $profileImage != "" && ((!$hasProfile || $isCreatingIntegrant))) {
				$profileImage = str_replace('data:image/jpeg;base64,', '', $profileImage);
				$this->storeSet('profile_image', $profileImage);
				/*$isIntegrantActive = $session->isIntegrantActive();
                if (!$isIntegrantActive) {
                    $uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg');
                } else {
                    $activeIntegrantID = $session->getActiveIntegrantClientID();
                    $uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg', NULL, $activeIntegrantID);
                }*/

			}
		} catch (Exception $e) {
			return \Drupal::messenger()->addMessage($translationService->translate('PERSONAL_DATA_FORM.INTERNAL_ERROR'), 'error');
		}

		if (!$hasProfile) {
			$this->storeSet('name', $name);
			$this->storeSet('surname', $surname);
			$this->storeSet('gender', $gender);
			$this->storeSet('birthdate', $birthdate);
			$this->storeSet('dni', $dni);
			$this->storeSet('integrant_type', $integrantType);
			$this->storeSet('email', $integrantEmail);
		} else {
			$user = \Drupal::service('gv_fplus_auth.user');

			try {
				if (!$isManagingIntegrant) {

					$updateResponse = $user->fanatics()->update(
						NULL,
						$session->getEmail(),
						$name,
						$surname,
						NULL,
						$dni,
						$gender,
						$finalBirthdate,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL
					);

				} else {

					/*$updateResponse = $user->fanatics()->update(
                        NULL,
                        $session->getEmail(),
                        $name,
                        $surname,
                        NULL,
                        $dni,
                        $gender,
                        $finalBirthdate,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NULL
                    );*/
					//ksm('update_response');
					//ksm($updateResponse->Response);
					/*
                    $integrant->update(
                        $sessionID,
                        $integrantID,
                        $integrantType,
                        $email,
                        $name,
                        $surname,
                        $surname2,
                        $IDCard,
                        $gender,
                        $birthdate,
                        $IDCountry,
                        $postalCode,
                        $city,
                        $IDProvince,
                        $provinceName,
                        $address,
                        $addressNumber,
                        $IDAddressType,
                        $addressMoreInfo,
                        $phoneNumber,
                        $telephoneNumber,
                        $renewPass,
                        $census,
                        $IDClub,
                        $clubIdentification
                    );*/

					if ($isManagingIntegrant) {
						$response = $integrant->update(
							$session->getIdentifier(),
							$session->getActiveIntegrantClientID(),
							$integrantType,
							$integrantEmail,
							$name,
							$surname,
							NULL,
							$dni,
							$gender,
							$finalBirthdate,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL
						);
					} else { // New integrant
						$response = $integrant->update(
							$session->getIdentifier(),
							$session->getActiveIntegrantClientID(),
							$integrantType,
							NULL,
							$name,
							$surname,
							NULL,
							$dni,
							$gender,
							$finalBirthdate,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL,
							NULL
						);
					}

				}
			} catch (ClientException $e) {
				if ($e->getResponse()->getStatusCode() == 409) {
					return \Drupal::messenger()->addMessage($translationService->translate('PERSONAL_DATA_FORM.EMAIL_ALREADY_EXISTS'), 'error');
				} else {
					throw $e;
				}
			} catch (\Exception $e) {
				\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
				return \Drupal::messenger()->addMessage($translationService->translate('PERSONAL_DATA_FORM.INTERNAL_ERROR'), 'error');
			}
		}

		$final_destination = $this->storeGet('destination_url');
		$this->deleteStoreKeys(['has_profile']);

		if ((!isset($final_destination) || (\Drupal::routeMatch()->getRouteName() == 'gv_fplus_auth.user_profile_personal_data_form'))) {
			$form_state->setResponse(new RedirectResponse(Url::fromRoute('gv_fplus_auth.user_profile_residence_data_form')->toString(), 307));
		} else {
			$this->deleteStoreKeys(['destination_url']);
			$form_state->setResponse(new TrustedRedirectResponse($final_destination, 307));
		}

	}
}

?>
