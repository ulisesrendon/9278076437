<?php

namespace Drupal\gv_fanatics_plus_utils;

/**
 * Clase utilitaria para facilitar encriptación y desencriptación de datos
 */
class Crypto {
	const ENCRYPTION_KEY = 'mfZEbljN90^$e4OE';
	const ENCRYPTION_METHOD = 'aes-128-ctr';
	
	public static function encrypt($input) {
		$enc_key = openssl_digest(Crypto::ENCRYPTION_KEY, 'SHA256', TRUE);
  		$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(Crypto::ENCRYPTION_METHOD));
		$crypted_token = openssl_encrypt($input, Crypto::ENCRYPTION_METHOD, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
  		unset($enc_key, $enc_iv);
		return $crypted_token;
	}
	
	public static function decrypt($output) {		
		list($crypted_token, $enc_iv) = explode("::", $output);;
  		$enc_key = openssl_digest(Crypto::ENCRYPTION_KEY, 'SHA256', TRUE);
  		$token = openssl_decrypt($crypted_token, Crypto::ENCRYPTION_METHOD, $enc_key, 0, hex2bin($enc_iv));
 		unset($crypted_token, $cipher_method, $enc_key, $enc_iv);
		return $token;
	}
}

?>
