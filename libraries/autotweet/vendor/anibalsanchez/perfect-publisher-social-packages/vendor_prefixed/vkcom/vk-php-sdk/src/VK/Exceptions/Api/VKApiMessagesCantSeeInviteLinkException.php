<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Exceptions\Api;

use XTS_BUILD\VK\Client\VKApiError;
use XTS_BUILD\VK\Exceptions\VKApiException;

/**
 */
class VKApiMessagesCantSeeInviteLinkException extends VKApiException {

	/**
	 * VKApiMessagesCantSeeInviteLinkException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(919, 'You can\'t see invite link for this chat', $error);
	}
}
