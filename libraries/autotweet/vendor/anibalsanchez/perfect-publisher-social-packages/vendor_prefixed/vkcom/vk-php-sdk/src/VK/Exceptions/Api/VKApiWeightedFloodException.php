<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Exceptions\Api;

use XTS_BUILD\VK\Client\VKApiError;
use XTS_BUILD\VK\Exceptions\VKApiException;

/**
 */
class VKApiWeightedFloodException extends VKApiException {

	/**
	 * VKApiWeightedFloodException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(601, 'Permission denied. You have requested too many actions this day. Try later.', $error);
	}
}
