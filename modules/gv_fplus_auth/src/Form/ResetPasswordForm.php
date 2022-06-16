<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

class ResetPasswordForm extends FormBase {

	private $session;
	private $user;
	private $channelResolver;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_reset_password_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		
		$this->session = $session;
		$this->user = $user;
		$this->channelResolver = $channelResolver;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {		
		$request = $this->getRequest();
		$token = $request->query->get('token');
		
		if (!isset($token)) {
			\Drupal::messenger()->addMessage($this->t('Your reset password request is invalid (token is not set). Please try again.'), 'error');
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$decrypted_email = NULL;
		try {
			$decrypted_email = $this->user->decryptEmail($token)->Mail;
		} catch(\Exception $e) {
			\Drupal::messenger()->addMessage($this->t('Your reset password request is invalid (internal error on request). Please try again.'), 'error');
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		if (!isset($decrypted_email)) {
			\Drupal::messenger()->addMessage($this->t('Your reset password request is invalid (could not decrypt email). Please try again.'), 'error');
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$valid_email = \Drupal::service('email.validator')->isValid($decrypted_email);
		if (!$valid_email) {
			\Drupal::messenger()->addMessage($this->t('Your reset password request is invalid (invalid email format). Please try again.'), 'error');
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$isIntegrant = $request->query->get('is_integrant');
		if (isset($isIntegrant) && $isIntegrant == '1') {
			$isIntegrant = TRUE;
		} else {
			$isIntegrant = FALSE;
		}
		
		$loginLink = Url::fromRoute('gv_fplus_auth.login_form', ['email' => $decrypted_email])->toString();

		$this->getRequest()->getSession()->set('gv_fplus_reset_password_email', $decrypted_email);
		$this->getRequest()->getSession()->set('gv_fplus_reset_password_is_integrant', $isIntegrant);

		$form['#prefix'] = '<div class="container">';
		
		$loader = \Drupal::service('domain.negotiator');
		$current_domain_id = $loader->getActiveDomain()->getDomainId();
 		$markup = '<div id="fplus-login-cta"><img src="/themes/contrib/bootstrap_sass/Assets/gv_my_grandski/logo_my_grandski/SVG/logo_mygrandski_login'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /><h2>' . $this->t('Create new password') . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,        
        ];
		
		$markup_current_email = '<div id="fplus-current-email-container"><span class="current-email">' . $decrypted_email . '</span></div>';
		$form['markup_current_email'] = [
            '#type' => 'markup',
            '#markup' => $markup_current_email,        
        ];
		
		$form['password'] = array(
			'#type' => 'password', 
			'#title' => $this->t('Password'), 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-register-password']
		);
		
		if ($isIntegrant) {
			$form['newsletter'] = array(
	   			'#type' => 'radios',
	   			'#title' => $this->t('Quiero recibir información a través de Newsletter', [], ['langcode' => 'es']),
    			'#required' => TRUE,
    			'#options' => [
    			    1 => $this->t('Si, accepto la clausula de consentimiento de envío de newsletters', [], ['langcode' => 'es']),
    				0 => $this->t('No', [], ['langcode' => 'es'])
    			],
	     		'#prefix' => '<div class="col-md-12 col-sm-12 col-xs-12">',
      			'#suffix' => '</div>'
	 		);
			
			$form['legal_consent'] = array(
    			'#type' => 'checkbox',
    			'#title' => $this->t('I have read and accept the MyGrandSki consent form'),
    			'#return_value' => 1,
    			'#default_value' => 0
  			);
		}
				
		$form['actions']['submit'] = [
    		'#type' => 'submit',
    		'#value' => $this->t('Create new password'),
  		];
		
		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';
		
		$form['#suffix'] = '<div class="login-link-container"><span class="label">' . $this->t('Do you remember your password?') . '</span><a href="' . $loginLink . '">' . $this->t('Login') . '</a></div></div>';
		
		$form['#attached']['library'][] = 'gv_fplus_auth/reset-password-form';
		$form['#cache']['contexts'][] = 'session';
		
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$isIntegrant = $this->getRequest()->getSession()->get('gv_fplus_reset_password_is_integrant');
		if ($isIntegrant) {
			$legalConsent = $form_state->getValue('legal_consent');
			if (!isset($legalConsent) || $legalConsent != 1) {
				$form_state->setErrorByName('legal_consent', $this->t('You must accept the terms and conditions'));
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$email = $this->getRequest()->getSession()->get('gv_fplus_reset_password_email');
		$isIntegrant = $this->getRequest()->getSession()->get('gv_fplus_reset_password_is_integrant');
		
		$password = $form_state->getValue('password');
		
		try {
			$response = $this->user->resetPassword($email, $password);
			if ($isIntegrant) {
				$newsletter = $form_state->getValue('newsletter');
				if (isset($newsletter) && $newsletter == 1) {
					$newsletter = TRUE;
				} else {
					$newsletter = FALSE;
				}
				
				$this->user->updateReceiveInformation(NULL, $email, $newsletter, $this->channelResolver->resolve()->dbm_id());
			}
		} catch (\Exception $e) {
			return \Drupal::messenger()->addMessage($this->t('An internal error ocurred'), 'error');
		}
		
		\Drupal::messenger()->addMessage($this->t('Your password was successfully reset. Please login with your new credentials.'));
		return $form_state->setRedirect('gv_fplus_auth.login_form', ['email' => $email]);
	}
}

?>