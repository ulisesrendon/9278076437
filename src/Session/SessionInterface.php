<?php

namespace Drupal\gv_fplus\Session;

/**
 * Interfaz para el objeto de sesión.
 * Inspirado en la interfaz estándar de Symfony para sesiones de servidor.
 *
 * @see https://github.com/symfony/symfony/blob/5.0/src/Symfony/Component/HttpFoundation/Session/SessionInterface.php
 */
interface SessionInterface
{
	/**
	 * Inicia el ciclo de vida de una sesión
	 * @param $createNewSession TRUE para forzar la creación de una nueva sesión, FALSE en caso contrario
	 */
	public function start($createNewSession = FALSE);
	
	/**
	 * Refresca los datos de sesión.
	 * @param $createNewSession TRUE para forzar la creación de una nueva sesión, FALSE en caso contrario
	 */
	public function refresh($createNewSession = FALSE);
	
	/**
	 * Indica si una sesión está activa (usuario activo) y autenticada (no es anónima)
	 */
	public function isActiveAndLogged();
	
	/**
	 * Indica si una sesión está activa (usuario activo)
	 */
	public function isActive();
	
	/**
	 * Autentica la sesión actual con las credenciales de usuario
	 * @param $username Nombre de usuario
	 * @param $password Contraseña de usuario
	 */
	public function login($username, $password);
	
	/**
	 * Cerra la sesión actual
	 */
	public function logout();
}

?>
