<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de registro básico
 */
class BasicRegistrationForm extends FormBase {

	private $session;
	private $apiClient;
	private $user;
	private $channelResolver;
	private $translationService;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_basic_register_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$user = \Drupal::service('gv_fplus_auth.user');
		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->user = $user;
  		$this->channelResolver = $channelResolver;
		$this->translationService = $translationService;
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$current_email = $request->query->get('email');
		
		$valid_email = \Drupal::service('email.validator')->isValid($current_email);
		if (!$valid_email || strlen($current_email) > 200) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$resetEmailLink = Url::fromRoute('gv_fplus_auth.email_check_form')->toString();
		
		$request->getSession()->set('gv_fplus_register_form_email', $current_email);
		
		$form['#prefix'] = '<div class="container">';

        $loader = \Drupal::service('domain.negotiator');
        $current_domain_id = $loader->getActiveDomain()->getDomainId();
 		$markup = '<div id="fplus-login-cta"><img src="/themes/contrib/bootstrap_sass/Assets/gv_my_grandski/logo_my_grandski/SVG/logo_mygrandski_login'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /><h2>' 
 		. $this->translationService->translate('BASIC_REGISTER_FORM.MAIN_TITLE') /*$this->t('Create your MyGrandSki account', [], ['context' => TranslationContext::LOGIN])*/ . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,
        ];

        $markup_current_email = '<div id="fplus-current-email-container"><span class="current-email">' . $current_email . '</span><a class="change-email-btn" href="' 
        . $resetEmailLink . '">' . $this->translationService->translate('BASIC_REGISTER_FORM.CHANGE_EMAIL_LINK') . '</a></div>';
		$form['markup_current_email'] = [
            '#type' => 'markup',
            '#markup' => $markup_current_email,        
        ];
		
		$form['name'] = array('#type' => 'textfield', '#title' => $this->translationService->translate('BASIC_REGISTER_FORM.NAME_FORM_TITLE'), '#required' => TRUE);
		$form['password'] = array(
			'#type' => 'password', 
			'#title' => $this->translationService->translate('BASIC_REGISTER_FORM.PASSWORD_FORM_TITLE'), 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-register-password']
		);
		
		$currentChannel = $this->channelResolver->resolve();
		$newsletterUrl = $this->t('Yes, <a href="https://www.grandvalira.com/en/consent-clause" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
		$myGrandskiUrl = $this->t('I have read and accept the <a href="https://www.grandvalira.com/en/clause-form-my-grandski-fanatics-plus" target="_blank">MyGrandSki consent form</a>', [], ['context' => TranslationContext::LOGIN]);
		if ($currentChannel->isPlus()) {
			$newsletterUrl = $this->t('Yes, <a href="https://www.grandvalira.com/en/consent-clause" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
		} else if ($currentChannel->isTemporadaOA()) {
			$newsletterUrl = $this->t('Yes, <a href="https://www.ordinoarcalis.com/en/consent-clause-newsletter" target="_blank">I accept the newsletter consent clause</a>', [], ['context' => TranslationContext::LOGIN]);
			$myGrandskiUrl = $this->t('I have read and accept the <a href="https://www.ordinoarcalis.com/en/consent-clause-mygrandski-profile" target="_blank">MyGrandSki consent form</a>', [], ['context' => TranslationContext::LOGIN]);
		}
		
		$form['newsletter'] = array(
    		'#type' => 'radios',
    		'#title' => $this->translationService->translate('BASIC_REGISTER_FORM.NEWSLETTER_FORM_TITLE') /*$this->t('I want to receive information by email', [], ['context' => TranslationContext::LOGIN])*/,
    		'#required' => TRUE,
    		'#options' => [
    			1 => $newsletterUrl,
    			0 => $this->t('No', [], ['context' => TranslationContext::LOGIN])
    		],
  		);
		
		$form['legal_consent'] = array(
    		'#type' => 'checkbox',
    		'#title' => $myGrandskiUrl,
    		'#return_value' => 1,
    		'#default_value' => 0,
  		);
		
		$form['actions']['submit'] = [
    		'#type' => 'submit',
    		'#value' => $this->translationService->translate('BASIC_REGISTER_FORM.CREATE'),
  		];
		
		$form['#cache']['contexts'][] = 'session';
		
		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';
		
		$form['#suffix'] = '</div>';
		
		$form['#attached']['library'][] = 'gv_fplus_auth/basic-register-form';
		
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$legalConsent = $form_state->getValue('legal_consent');
		if (!$legalConsent) {
			$form_state->setErrorByName('legal_consent', $this->translationService->translate('BASIC_REGISTER_FORM.TERMS_CONDITIONS_ERROR'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$name = $form_state->getValue('name');
		$email = $this->getRequest()->getSession()->get('gv_fplus_register_form_email');
		$this->getRequest()->getSession()->remove('gv_fplus_register_form_email');
		
		$password = $form_state->getValue('password');
		
		$newsletter = $form_state->getValue('newsletter');
		$newsletter = $newsletter > 0 ? true : false;
		
		$legalConsent = $form_state->getValue('legal_consent');
		
		$this->session->start(TRUE);
		$sessionID = $this->session->getIdentifier();
		$languageID = $this->session->getIDLanguage();
		$salesChannelID = $this->session->getIDSalesChannel();
		
		$response = $this->user->basicRegister($sessionID, $email, $name, $password, $newsletter);
		$userID = $response->ClientID;
		
		//$updateResponse = $this->apiClient->users()->updateReceiveInformation(NULL, $email, $newsletter, $salesChannelID);
		$activationResponse = $this->user->sendActivation($email, $languageID, $salesChannelID);
		if (!isset($activationResponse) || $activationResponse->EmailSent == FALSE) {
			\Drupal::messenger()->addMessage($this->translationService->translate('REMEMBER_PASS_FORM.EMAIL_SEND_ERROR'), 'error');
		}
		
		$this->getRequest()->getSession()->set('gv_fplus_activate_email', $email);
		$form_state->setRedirect('gv_fplus_auth.activation_email_sent_form');
	}
}

?>
