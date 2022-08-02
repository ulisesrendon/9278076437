<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de autenticación para verificación de email
 */
class EmailCheckForm extends FormBase {

	private $session;
	private $apiClient;
	private $user;
	private $translationService;
	/**
	 * Email validator
	 * @var \Egulias\EmailValidator\EmailValidator
	 */
	protected $emailValidator;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_email_check_form';
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
		$this->emailValidator = new EmailValidator;
  	}

	public function getPageTitle() {		
		return $this->translationService->translate('EMAIL_CHECK_FORM.PAGE_TITLE');
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$current_email = $request->query->get('email');
		$sessionExpiredNotice = $request->query->get('expired');
		
		$isActiveAndLogged = $this->session -> isActiveAndLogged();
		if ($isActiveAndLogged) {
			return new RedirectResponse(Url::fromRoute('<front>')->toString());
		}
		
		if (isset($sessionExpiredNotice) && $sessionExpiredNotice == 1) {
			$session_expired = '<div class="end-session danger">'. $this->translationService->translate('EMAIL_CHECK_FORM.SESSION_EXPIRED_NOTICE') . '</div>';
		} else {
			$session_expired = '';
		}
		
		$form['#prefix'] = $session_expired.'<div class="container">';
		
		$originalUrl = $request->query->get('original_url');
		if (isset($originalUrl) && strlen($originalUrl) > 0) {
			$request->getSession()->set('gv_fplus_original_url', $originalUrl);
		}
		
		$loader = \Drupal::service('domain.negotiator');
		$current_domain_id = $loader->getActiveDomain()->getDomainId();
		$markup = '<div id="fplus-login-cta"><img src="/themes/contrib/bootstrap_sass/Assets/gv_my_grandski/logo_my_grandski/SVG/logo_mygrandski_login'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /><h2>' . $this->translationService->translate('EMAIL_CHECK_FORM.MAIN_TITLE') /*$this->t('Write your email', [], ['context' => TranslationContext::LOGIN])*/ . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,        
        ];

		$form['email'] = array(
			'#type' => 'email', 
			'#title' => $this->translationService->translate('EMAIL_CHECK_FORM.EMAIL_FIELD_LABEL'), 
			'#required' => TRUE, '#default_value' => $current_email, 
			'#maxlength' => 200,
			'#suffix' => '<div class="forgotten-email-container"><a class="use-ajax" href="' 
				. Url::fromRoute('gv_fplus_auth.forgotten_email_modal')->toString() . '">' . $this->translationService->translate('EMAIL_CHECK_FORM.FORGOTTEN_EMAIL_LABEL') .'</a></div>'
		);
		
		$form['actions']['submit'] = [
    		'#type' => 'submit',
    		'#value' => $this->translationService->translate('EMAIL_CHECK_FORM.NEXT')
  		];
		
		$form['#cache']['contexts'][] = 'session';
		
		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';
		
		$form['#suffix'] = '</div>';
		
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fplus_auth/email-check-form';
		
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$email = trim($form_state->getValue('email'));
		$domain = explode("@", $email)[1];
		if (!checkdnsrr($domain,"MX")) {
			$form_state->setErrorByName("email", $this->translationService->translate('LOGIN_FORM.INVALID_MAIL'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$email = $form_state->getValue('email');
		$userExists = $this->user->exists($email);

		// A - Email exists and it is active
		// B - Email exists but is inactive
		// C - Email does not exist
		
		if (!$userExists) {
			$form_state->setRedirect('gv_fplus_auth.basic_register_form', [], ['query' => ['email' => $email]]);
		} else {
			$isVerified = $this->user->isVerified($email);
			if ($isVerified) {
				$queryParams = ['query' => ['email' => $email]];
				
				$request = $this->getRequest();
				$originalUrl = 	$request->getSession()->get('gv_fplus_original_url');
				$request->getSession()->remove('gv_fplus_original_url');
				
				if (isset($originalUrl) && strlen($originalUrl) > 0) {
					$queryParams['query']['original_url'] = $originalUrl;
				}
				
				$form_state->setRedirect('gv_fplus_auth.login_form', [], $queryParams);
			} else {
				$this->session->start(TRUE);
				$sessionID = $this->session->getIdentifier();
				$languageID = $this->session->getIDLanguage();
				$salesChannelID = $this->session->getIDSalesChannel();
				
				$activationResponse = $this->user->sendActivation($email, $languageID, $salesChannelID);
				$this->getRequest()->getSession()->set('gv_fplus_activate_email', $email);
				$form_state->setRedirect('gv_fplus_auth.activation_email_sent_form');
			}
		}
	}
}

?>
