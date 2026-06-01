<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Actions;

use XTS_BUILD\VK\Client\VKApiRequest;
use XTS_BUILD\VK\Exceptions\VKApiException;
use XTS_BUILD\VK\Exceptions\VKClientException;

/**
 */
class Gifts {

	/**
	 * @var VKApiRequest
	 */
	private $request;

	/**
	 * Gifts constructor.
	 *
	 * @param VKApiRequest $request
	 */
	public function __construct(VKApiRequest $request) {
		$this->request = $request;
	}

	/**
	 * Returns a list of user gifts.
	 *
	 * @param string $access_token
	 * @param array $params 
	 * - @var integer user_id: User ID.
	 * - @var integer count: Number of gifts to return.
	 * - @var integer offset: Offset needed to return a specific subset of results.
	 * @throws VKClientException
	 * @throws VKApiException
	 * @return mixed
	 */
	public function get($access_token, array $params = []) {
		return $this->request->post('gifts.get', $access_token, $params);
	}
}
