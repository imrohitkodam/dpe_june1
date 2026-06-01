<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Exceptions\Api;

use XTS_BUILD\VK\Client\VKApiError;
use XTS_BUILD\VK\Exceptions\VKApiException;

/**
 */
class VKApiAccessVideoException extends VKApiException {

	/**
	 * VKApiAccessVideoException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(204, 'Access denied', $error);
	}
}
