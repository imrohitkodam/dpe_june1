<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Exceptions\Api;

use XTS_BUILD\VK\Client\VKApiError;
use XTS_BUILD\VK\Exceptions\VKApiException;

/**
 */
class VKApiMessagesEditExpiredException extends VKApiException {

	/**
	 * VKApiMessagesEditExpiredException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(909, 'Can\'t edit this message, because it\'s too old', $error);
	}
}
