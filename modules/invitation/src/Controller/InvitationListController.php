<?php

namespace Drupal\gv_fanatics_plus_invitation\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador del listado de invitaciones
 */
class InvitationListController extends ControllerBase {

	private $invitation;
	private $session;
	private $channelResolver;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$invitation = $container->get('gv_fanatics_plus_invitation.invitation');
		$channelResolver = $container->get('gv_fplus.channel_resolver');
		
		return new static($session, $invitation, $channelResolver);
	}
	
	public function __construct($session, $invitation, $channelResolver) {
		$this->session = $session;
		$this->invitation = $invitation;
		$this->channelResolver = $channelResolver;
	}

	public function invitationList() {
		$invitations = $this->invitation->getAll($this->session->getIdentifier());
		$activeChannel = $this->channelResolver->resolve();
		return [
			'#attached' => [
				'library' => [
					'core/drupal.dialog.ajax',
					'gv_fanatics_plus_invitation/invitation_list'
				], 
			], 
			'#theme' => 'gv_fanatics_plus_invitation_list', 
			'#invitations' => $invitations, 
			'#sales_channel' => $activeChannel
		];
	}

}

?>
