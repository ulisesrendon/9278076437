<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de correo de restablecimiento de contraseña enviado
 */
class ResetPasswordEmailSentForm extends FormBase {

	private $session;
	private $apiClient;
	private $user;
	private $translationService;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_reset_password_email_sent_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$user = \Drupal::service('gv_fplus_auth.user');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->user = $user;
		$this->translationService = $translationService;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$current_email = $request->getSession()->get('gv_fplus_reset_password_email');
		
		$valid_email = \Drupal::service('email.validator')->isValid($current_email);
		if (!$valid_email) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$loginFormUrl = Url::fromRoute('gv_fplus_auth.login_form', ['email' => $current_email])->toString();
				
		$form['#prefix'] = '<div class="container">';
		
		$loader = \Drupal::service('domain.negotiator');
		$current_domain_id = $loader->getActiveDomain()->getDomainId();
 		$markup = '<div id="fplus-activation-email-cta"><div class="img-act"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/activation_ok'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /></div><h2>' 
 		. $this->translationService->translate('REMEMBER_PASS_FORM.RESET_PASSWORD_EMAIL_SENT') . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,        
        ];

		$form['markup_description'] = [
			'#type' => 'markup',
			'#markup' => '<p class="main-text">' 
			. $this->translationService->translate('REMEMBER_PASS_FORM.RESET_PASSWORD_EMAIL_INSTRUCTIONS', ['@email' => $current_email]) . '</p>'
		];

		$form['actions']['submit'] = array(
    		'#type' => 'submit',
    		'#value' => $this->t('Reenviar', [], ['langcode' => 'es']),
    		'#prefix' => '<div class="recover-container"><p>' 
    		. $this->translationService->translate('ACTIVATION_EMAIL_SENT_FORM.CANNOT_FIND_EMAIL') . '</p>',
		    '#suffix' => '</div>'
  		);
		
		$form['markup_already_active'] = [
			'#type' => 'markup',
			'#markup' => '<p><span>' 
			. $this->translationService->translate('REMEMBER_PASS_FORM.LOGIN_LINK_INTRO') . '</span>' . '<a href="' . $loginFormUrl . '">' 
			. $this->translationService->translate('REMEMBER_PASS_FORM.LOGIN_LINK') . '</a></p>'
		];
		
		$form['#cache']['contexts'][] = 'session';
		
		$form['#suffix'] = '</div>';
		
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$this->session->start(TRUE);
		$sessionID = $this->session->getIdentifier();
		$languageID = $this->session->getIDLanguage();
		$salesChannelID = $this->session->getIDSalesChannel();
		
		$request = $this->getRequest();
		$current_email = $request->getSession()->get('gv_fplus_reset_password_email');
		
		$destination_url = Url::fromRoute('gv_fplus_auth.remember_password_form', ['email' => $current_email]);
		return $form_state->setResponse( new TrustedRedirectResponse($destination_url->toString(), 307) );
	}
}

?>