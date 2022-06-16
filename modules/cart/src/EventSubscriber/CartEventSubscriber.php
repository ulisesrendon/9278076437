<?php

namespace Drupal\gv_fanatics_plus_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\Event;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\gv_fanatics_plus_cart\Event\CartEntityAddEvent;
use Drupal\gv_fanatics_plus_cart\Event\CartEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Suscriptor de eventos del carrito de usuario
 */
class CartEventSubscriber implements EventSubscriberInterface {

	use StringTranslationTrait;

	/**
	 * The messenger.
	 *
	 * @var \Drupal\Core\Messenger\MessengerInterface
	 */
	protected $messenger;

	/**
	 * Constructs a new CartEventSubscriber object.
	 *
	 * @param \Drupal\Core\Messenger\MessengerInterface $messenger
	 *   The messenger.
	 * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
	 *   The string translation.
	 */
	public function __construct(MessengerInterface $messenger, TranslationInterface $string_translation) {
		$this -> messenger = $messenger;
		$this -> stringTranslation = $string_translation;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents() {
		$events = [CartEvents::CART_BOOKING_SERVICE_ADD => 'displayAddToCartMessage'];
		return $events;
	}

	public function displayAddToCartMessage(Event $event) {}

}

?>
