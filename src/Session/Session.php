<?php

namespace Drupal\gv_fplus\Session;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
 * Objeto que representa una sesión del frontal.
 * 
 * @see Para una referencia del objeto en el BackEnd, consultar: http://pruebasapi.grandvalira.com/swagger/ui/index#!/Session/WebDataSession_Session_0
 */
class Session implements SessionInterface {
	const SESSION_STORAGE_PREFIX = 'gv_fplus_session';
	
	private $sessionManager;
	private $dbmClient;
	private $currencyResolver;
	private $languageResolver;
	private $channelResolver;
	private $currentUser;
	
	/**
	 * Identificador de sesión de Doblemente
	 */
	private $Identifier;
	
	/**
	 * Identificador de usuario de Doblemente
	 */
	private $IDUser;
	
	/**
	 * Email de usuario
	 */
	private $Email;
	
	/**
	 * Fecha de inicio de sesión
	 */
	private $StartDate;
	
	/**
	 * Fecha de último acceso
	 */
	private $LastAccessDate;
	
	/**
	 * Dirección de IP del usuario
	 * No se utiliza actualmente
	 */
	private $IP;
	
	/**
	 * Identificador del navegador
	 * No se utiliza actualmente
	 */
	private $Browser;
	
	/**
	 * Identificador de idioma
	 */
	private $IDLanguage;
	
	/**
	 * Identificador de canal de venta
	 */
	private $IDSalesChannel;
	
	/**
	 * No se utiliza actualmente
	 */
	private $Languages;
	
	/**
	 * Referer http
	 * No se utiliza actualmente
	 */
	private $Referrer;
	
	/**
	 * String de useragent de navegador
	 * No se utiliza actualmente
	 */
	private $UserAgent;
	
	/**
	 * Flag que indica si la sesión de BackEnd ha expirado
	 */
	private $Expired;
	
	/**
	 * Identificador de moneda
	 */
	private $IDCurrency;
	
	/**
	 * Identificador de cliente
	 */
	private $IDClient;
	
	/**
	 * Indica si se está gestionando un integrante
	 */
	private $isManagingIntegrant;
	
	/**
	 * Indica el ID de cliente del integrante que se está gestionando (si aplicable)
	 */
	private $activeIntegrantClientID;
	
	/**
	 * Indica si se está creando un integrante
	 */
	private $isCreatingIntegrant;
	
	/**
	 * Identificador de funcionalidad MemberGetMember
	 */
	private $bookingReferral;
	
	/**
	 * Flag que indica si se debe sobreescribir el código de colectivo introducido por el usuario
	 */
	private $overrideCollective;
	
	/**
	 * Referencia a una URL de redirección futura, se hace servir en algunos escenarios
	 */
	private $originalRedirectUrl;
	
	/**
	 * Flag que indica si la sesión corresponde a un agente de Taquillas de Grandvalira
	 */
	private $isBoxOfficeAgent;
	
	public function __construct() {
		
		$sessionManager = \Drupal::service('user.private_tempstore')->get(static::SESSION_STORAGE_PREFIX);
		$dbmClient = \Drupal::service('gv_fplus_dbm_api.client');
		
		$currencyResolver = \Drupal::service('gv_fplus.currency_resolver');
		$languageResolver = \Drupal::service('gv_fplus.language_resolver');
		$channelResolver = \Drupal::service('gv_fplus.channel_resolver');
		
		$currentUser =  \Drupal::currentUser();
		
		$this->sessionManager = $sessionManager;
		$this->dbmClient = $dbmClient;
		$this->currencyResolver = $currencyResolver;
		$this->languageResolver = $languageResolver;
		$this->channelResolver = $channelResolver;
		$this->currentUser = $currentUser;
	}
	
	/**
	 * Función auxiliar para obtener un valor de la sesión nativa de Drupal
	 */
	private function _getAttribute($attrName, $defaultValue = NULL) {
		return $this->sessionManager->get($attrName, $defaultValue);
	}
	
	/**
	 * Función auxiliar para establecer un valor de la sesión nativa de Drupal
	 */
	private function _setAttribute($attrName, $attrValue) {
		return $this->sessionManager->set($attrName, $attrValue);
	}
	
	/**
	 * Función auxiliar para borrar un valor de la sesión nativa de Drupal
	 */
	private function _removeAttribute($attrName) {
		return $this->sessionManager->delete($attrName);
	}
	
	/**
	 * Función auxiliar para cargar datos de sesión almacenados
	 */
	private function _loadFromNativeSession() {
		$this->Identifier = $this->_getAttribute('Identifier');
		$this->IDUser = $this->_getAttribute('IDUser');
		$this->Email = $this->_getAttribute('Email');
		$this->StartDate = $this->_getAttribute('StartDate');
		$this->LastAccessDate = $this->_getAttribute('LastAccessDate');
		$this->IP = $this->_getAttribute('IP');
		$this->Browser = $this->_getAttribute('Browser');
		$this->IDLanguage = $this->_getAttribute('IDLanguage');
		$this->IDSalesChannel = $this->_getAttribute('IDSalesChannel');
		$this->Languages = $this->_getAttribute('Languages');
		$this->Referrer = $this->_getAttribute('Referrer');
		$this->UserAgent = $this->_getAttribute('UserAgent');
		$this->Expired = $this->_getAttribute('Expired');
		$this->IDCurrency = $this->_getAttribute('IDCurrency');
		
		$this->IDClient = $this->_getAttribute('IDClient');
		
		$this->isManagingIntegrant = $this->_getAttribute('IsManagingIntegrant');
		$this->isCreatingIntegrant = $this->_getAttribute('IsCreatingIntegrant');
		$this->activeIntegrantClientID = $this->_getAttribute('ActiveIntegrantClientID');
		
		$this->bookingReferral = $this->_getAttribute('BookingReferral');
		$this->overrideCollective = $this->_getAttribute('OverrideCollective');
		$this->originalRedirectUrl = $this->_getAttribute('OriginalRedirectUrl');
		
		$this->isBoxOfficeAgent = $this->_getAttribute('IsBoxOfficeAgent');
	}
	
	/**
	 * Función auxiliar persistir datos de sesión
	 */
	private function _saveToNativeSession() {
		$this->_setAttribute('Identifier', $this->Identifier);
		$this->_setAttribute('IDUser', $this->IDUser);
		$this->_setAttribute('Email', $this->Email);
		$this->_setAttribute('StartDate', $this->StartDate);
		$this->_setAttribute('LastAccessDate', $this->LastAccessDate);
		$this->_setAttribute('IP', $this->IP);
		$this->_setAttribute('Browser', $this->Browser);
		$this->_setAttribute('IDLanguage', $this->IDLanguage);
		$this->_setAttribute('IDSalesChannel', $this->IDSalesChannel);
		$this->_setAttribute('Languages', $this->Languages);
		$this->_setAttribute('Referrer', $this->Referrer);
		$this->_setAttribute('UserAgent', $this->UserAgent);
		$this->_setAttribute('Expired', $this->Expired);
		$this->_setAttribute('IDCurrency', $this->IDCurrency);
		
		$this->_setAttribute('IDClient', $this->IDClient);
		
		$this->_setAttribute('IsManagingIntegrant', $this->isManagingIntegrant);
		$this->_setAttribute('IsCreatingIntegrant', $this->isCreatingIntegrant);
		$this->_setAttribute('ActiveIntegrantClientID', $this->activeIntegrantClientID);
		
		$this->_setAttribute('BookingReferral', $this->bookingReferral);
		$this->_setAttribute('OverrideCollective', $this->overrideCollective);
		$this->_setAttribute('OriginalRedirectUrl', $this->originalRedirectUrl);
		
		$this->_setAttribute('IsBoxOfficeAgent', $this->isBoxOfficeAgent);
	}
	
	/**
	 * Función auxiliar para borrar todos los datos de sesión persistidos
	 */
	private function _clearSession() {
		$this->_removeAttribute('Identifier');
		$this->_removeAttribute('IDUser');
		$this->_removeAttribute('Email');
		$this->_removeAttribute('StartDate');
		$this->_removeAttribute('LastAccessDate');
		$this->_removeAttribute('IP');
		$this->_removeAttribute('Browser');
		$this->_removeAttribute('IDLanguage');
		$this->_removeAttribute('IDSalesChannel');
		$this->_removeAttribute('Languages');
		$this->_removeAttribute('Referrer');
		$this->_removeAttribute('UserAgent');
		$this->_removeAttribute('Expired');
		$this->_removeAttribute('IDCurrency');
		
		$this->_removeAttribute('IDClient');
		
		$this->_removeAttribute('IsManagingIntegrant');
		$this->_removeAttribute('ActiveIntegrantClientID');
		$this->_removeAttribute('IsCreatingIntegrant');
		
		$this->_removeAttribute('BookingReferral');
		$this->_removeAttribute('OverrideCollective');
		$this->_removeAttribute('OriginalRedirectUrl');
		
		$this->_removeAttribute('IsBoxOfficeAgent');
		
		$this->Identifier = NULL;
		$this->IDUser = NULL;
		$this->Email = NULL;
		$this->StartDate = NULL;
		$this->LastAccessDate = NULL;
		$this->IP = NULL;
		$this->Browser = NULL;
		$this->IDLanguage = NULL;
		$this->IDSalesChannel = NULL;
		$this->Languages = NULL;
		$this->Referrer = NULL;
		$this->UserAgent = NULL;
		$this->Expired = NULL;
		$this->IDCurrency = NULL;
		
		$this->isManagingIntegrant = FALSE;
		$this->isCreatingIntegrant = FALSE;
		$this->activeIntegrantClientID = NULL;
		
		$this->bookingReferral = NULL;
		$this->overrideCollective = NULL;
		$this->originalRedirectUrl = NULL;
		
		$this->isBoxOfficeAgent = FALSE;
	}
	
	/**
	 * Presiste un identificador de referencia MemberGetMember
	 */
	public function saveBookingReferral($bookingReferral) {
		$this->bookingReferral = $bookingReferral;
		$this->_setAttribute('BookingReferral', $bookingReferral);
	}
	
	/**
	 * Borra un identificador de referencia MemberGetMember
	 */
	public function deleteBookingReferral() {
		$this->_removeAttribute('BookingReferral');
		$this->bookingReferral = NULL;
	}
	
	/**
	 * Setea la flag interna de $overrideCollective
	 */
	public function setOverrideCollective($overrideCollective) {
		$this->overrideCollective = $overrideCollective;
		$this->_setAttribute('OverrideCollective', $overrideCollective);
	}
	
	/**
	 * Define la flag interna de $isBoxOfficeAgent (agente de Taquillas de Grandvalira)
	 */
	public function setBoxOfficeAgent($isAgent) {
		$this->isBoxOfficeAgent = $isAgent;
		$this->_setAttribute('IsBoxOfficeAgent', $isAgent);
	}
	
	/**
	 * Define el valor correspondiente a una URL original 
	 */
	public function setOriginalRedirectUrl($originalUrl) {
		$this->originalRedirectUrl = $originalUrl;
		$this->_setAttribute('OriginalRedirectUrl', $originalUrl);
	}
	
	public function getOriginalRedirectUrl() {
		return $this->originalRedirectUrl;
	}
	
	/**
	 * Borra la referencia a una URL original
	 */
	public function deleteOriginalRedirectUrl() {
		$this->_removeAttribute('OriginalRedirectUrl');
		$this->originalRedirectUrl = NULL;
	}
	
	/**
	 * Inicia el ciclo de vida de una sesión
	 * @param $createNewSession TRUE para forzar la creación de una nueva sesión, FALSE en caso contrario
	*/
	public function start($createNewSession = FALSE) {
		$currencyID = $this->currencyResolver->resolve()->id();
		$activeLanguageID = $this->languageResolver->resolve()->id();
		$activeChannelID = $this->channelResolver->resolve()->dbm_id();
		
		// Start a manual session for anonymous users.
    	if ($this->currentUser->isAnonymous() && !isset($_SESSION['gv_fplus_auth_holds_session'])) {
      		$_SESSION['gv_fplus_auth_holds_session'] = true;
			$drupalSession = \Drupal::service('session');
      		//$this->sessionManager->start();
      		
      		$drupalSession->start();
			
    	}
		
		$this->_loadFromNativeSession();
		$currentSessionId = $this->Identifier;
		$createNewSession = FALSE;
		
		if (!$createNewSession && $currentSessionId != NULL) {
			try {
				$isActiveAndLogged = $this->dbmClient->session()->isActiveAndLogged($currentSessionId);
				//ksm($isActiveAndLogged);
				if ($isActiveAndLogged->Value) {
					$currentSessionData = $this->dbmClient->session()->getById($currentSessionId);
					
					if ($currentSessionData->IDSalesChannel == $activeChannelID) {
						// Update backend
						$this->dbmClient->session()->update($currentSessionId, $activeLanguageID, $activeChannelID, $currencyID);
						$this->refresh();
					} else {
						$createNewSession = TRUE;
					}
					
				} else {
					$createNewSession = TRUE;
				}
				
			} catch(\Exception $e) {
				$createNewSession = TRUE;
			}
		} else {
			$createNewSession = TRUE;
		}
		
		if ($createNewSession == TRUE) {
			$this->refresh(TRUE);
		}
	}
	
	/**
	 * Refresca los datos de sesión
	 * @param $createNewSession TRUE para forzar la creación de una nueva sesión, FALSE en caso contrario
	 * @param $isManagingIntegrant TRUE si se está gestionando un integrante, FALSE en caso contrario
	 * @param $isCreatingIntegrant TRUE si se está creando un integrante, FALSE en caso contrario
	 * @param $activeIntegrantClientID identificador de cliente integrante que se está gestionado, NULL si no se está gestionando un integrante
	 */
	public function refresh($createNewSession = FALSE, $isManagingIntegrant = NULL, $isCreatingIntegrant = NULL, $activeIntegrantClientID = NULL) {
		$identifier = $this->Identifier;
		if ($createNewSession) {
			$currencyID = $this->currencyResolver->resolve()->id();
			$activeLanguageID = $this->languageResolver->resolve()->id();
			$activeChannelID = $this->channelResolver->resolve()->dbm_id();
			
			$this->_clearSession();
			
			$newSession = $this->dbmClient->session()->create($activeLanguageID, $activeChannelID, $currencyID);
			$identifier = $newSession->Identifier;
		}
		
		$newSessionData = $this->dbmClient->session()->getById($identifier);
		$this->Identifier = $newSessionData->Identifier;
		$this->IDUser = $newSessionData->IDUser;
		$this->StartDate = $newSessionData->StartDate;
		$this->LastAccessDate = $newSessionData->LastAccessDate;
		$this->IP = $newSessionData->IP;
		$this->Browser = $newSessionData->Browser;
		$this->IDLanguage = $newSessionData->IDLanguage;
		$this->IDSalesChannel = $newSessionData->IDSalesChannel;
		$this->Languages = $newSessionData->Languages;
		
		if (isset($newSessionData->Referrer)) {
			$this->Referrer = $newSessionData->Referrer;
		}
		
		$this->UserAgent = $newSessionData->UserAgent;
		$this->Expired = $newSessionData->Expired;
		$this->IDCurrency = $newSessionData->IDCurrency;
		$this->IDClient = $newSessionData->IDClient;
		$this->Email = $newSessionData->Email;
		
		if ($isManagingIntegrant !== NULL) {
			$this->isManagingIntegrant = $isManagingIntegrant;
		}
		
		if ($isCreatingIntegrant !== NULL) {
			$this->isCreatingIntegrant = $isCreatingIntegrant;
		}
		
		if ($isCreatingIntegrant === NULL && $isManagingIntegrant === NULL) {
			$this->isManagingIntegrant = $this->_getAttribute('IsManagingIntegrant');
			$this->isCreatingIntegrant = $this->_getAttribute('IsCreatingIntegrant');
			$this->activeIntegrantClientID = $this->_getAttribute('ActiveIntegrantClientID');
		} else {
			$this->activeIntegrantClientID = $activeIntegrantClientID;	
		}
		
		$this->_saveToNativeSession();
	}
	
	/**
	 * Verifica si la sesión está activa y autenticada
	 */
	public function isActiveAndLogged() {
		if (!isset($this->Identifier)) {
			return FALSE;
		}
		
		/*if (!isset($this->Email)) {
			return FALSE;
		}
		
		if (!isset($this->IDClient)) {
			return FALSE;
		}
		*/
		
		$isActiveAndLogged = $this->dbmClient->session()->isActiveAndLogged($this->Identifier);
		if ($isActiveAndLogged->Value) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Verifica si una sesión está activa
	 */
	public function isActive() {
		if (!isset($this->Identifier)) {
			return FALSE;
		}
		
		$isActive = $this->dbmClient->session()->isActive($this->Identifier);
		if ($isActive->Value) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Autentica la sesión con credenciales de usuario
	 * 
	 * @return -2 si cuenta no está verificada, -1 si no está activa, 0 no es válida (credenciales inválidas), 1 en caso de éxito.
	 */
	public function login($username, $password) {
		try {
			$loginResult = $this->dbmClient->session()->login($this->Identifier, $username, $password);
			$active = $loginResult->Active;
			$verified = $loginResult->Verified;
		
			if (!$verified) {
				return -2;
			}
			
			if (!$active) {
				return -1;
			}
			
			$this->Email = $username;
			$this->IDClient = $loginResult->IDClient;
			
			$this->refresh();
			return 1;
		} catch (\GuzzleHttp\Exception\RequestException $e) {
    		if ($e->getResponse()->getStatusCode() == '400' || $e->getResponse()->getStatusCode() == '401') {
				return 0;
			} else {
				throw $e;
			}
		}
	}
	
	/**
	 * Autentica la sesión mediante acceso externo
	 */
	public function externalAccess($usToken, $clToken, $texToken, $expToken, $tqusu = NULL) {
		$activeChannelID = $this->channelResolver->resolve()->dbm_id();
		$httpParams = [
			['ParamName' => 'us', 'ParamValue' => $usToken],
			['ParamName' => 'cl', 'ParamValue' => $clToken],
			['ParamName' => 'tex', 'ParamValue' => $texToken]
		];
		
		if (isset($expToken)) {
			$httpParams[] =	['ParamName' => 'exp', 'ParamValue' => $expToken];
		}
		
		if (isset($tqusu) && strlen($tqusu) > 0) {
			$httpParams[] = ['ParamName' => 'tqusu', 'ParamValue' => $tqusu];
		}
		
		try {
			$loginResult = $this->dbmClient->session()->externalAccess($activeChannelID, $httpParams);
			
			$IDSession = $loginResult->IDSession;
			$IDBooking = $loginResult->IDBooking;
			$IDAccessType = $loginResult->IDAccesType;
			$UserVerified = $loginResult->UserVerified;
			$sessionResult = $loginResult->Session;
			$this->logout();
			
			if (!isset($tqusu) || strlen($tqusu) <= 0) {
				if ($UserVerified == FALSE) {
					if (isset($sessionResult->Email)) {
						return ['code' => -2, 'email' => $sessionResult->Email];
					} else {
						return NULL;
					}
				}
			}

			$this->Identifier = $IDSession;
			$this->refresh();
			
			if (isset($tqusu) && strlen($tqusu) > 0) {
				$this->setBoxOfficeAgent(TRUE);
			} else {
				$this->setBoxOfficeAgent(FALSE);
			}
			
			return $loginResult;
		} catch (ClientException $e) {
			return NULL;
		}
	}
	
	/**
	 * Autentica una sesión mediante una sesión encriptada
	 * Deprecado.
	 */
	public function externalLogin($encryptedSession) {
		$loginResult = $this->dbmClient->session()->getById($encryptedSession);
		
		$this->Identifier = $loginResult->Identifier;
		$this->IDUser = $loginResult->IDUser;
		$this->Email = $loginResult->Email;
		$this->Browser = $loginResult->Browser;
		$this->IDLanguage = $loginResult->IDLanguage;
		$this->IDSalesChannel = $loginResult->IDSalesChannel;
		$this->Expired = $loginResult->Expired;
		$this->IDCurrency = $loginResult->IDCurrency;
		$this->UserAgent = $loginResult->UserAgent;
		
		$this->refresh();
		return $loginResult;
	}
	
	/**
	 * Cierra la sesión actual
	 */
	public function logout() {
		if (!$this->Identifier) {
			return;
		}
		
		$response = $this->dbmClient->session()->logout($this->Identifier);
		$this->_clearSession();
		return $response;
	}
	
	public function getIdentifier() {
		return $this->Identifier;
	}
	
	public function getIDUser() {
		return $this->IDUser;
	}
	
	public function getEmail() {
		return $this->Email;
	}
	
	public function getIDCurrency() {
		return $this->IDCurrency;
	}
	
	public function getIDSalesChannel() {
		return $this->IDSalesChannel;
	}
	
	public function getIDLanguage() {
		return $this->IDLanguage;
	}
	
	public function getIDClient() {
		return $this->IDClient;
	}
	
	public function isManagingIntegrant() {
		return $this->isManagingIntegrant;
	}
	
	public function getActiveIntegrantClientID() {
		return $this->activeIntegrantClientID;
	}
	
	public function isCreatingIntegrant() {
		return $this->isCreatingIntegrant;
	}
	
	public function isIntegrantActive() {
		return ($this->isCreatingIntegrant || $this->isManagingIntegrant);
	}
	
	public function getClientName() {
		if (!$this->Email) {
			return NULL;
		}
		
	    return $this -> dbmClient -> users() -> getUserProfile($this -> Email) -> Name;
	}
	
	public function getClient() {
		if (!$this->Email) {
			return NULL;
		}
		
		return $this-> dbmClient -> users() -> getUserProfile($this->Email);
	}
	
	public function getBookingReferral() {
		return $this->bookingReferral;
	}
	
	public function overrideCollective() {
		return $this->overrideCollective;
	}
	
	public function isBoxOfficeAgent() {
		return ($this->isBoxOfficeAgent == TRUE);
	}
}

?>
