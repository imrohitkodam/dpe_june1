<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Actions;

use XTS_BUILD\VK\Client\VKApiRequest;
use XTS_BUILD\VK\Exceptions\VKApiException;
use XTS_BUILD\VK\Exceptions\VKClientException;

/**
 */
class Search {

	/**
	 * @var VKApiRequest
	 */
	private $request;

	/**
	 * Search constructor.
	 *
	 * @param VKApiRequest $request
	 */
	public function __construct(VKApiRequest $request) {
		$this->request = $request;
	}

	/**
	 * Allows the programmer to do a quick search for any substring.
	 *
	 * @param string $access_token
	 * @param array $params 
	 * - @var string q: Search query string.
	 * - @var integer offset: Offset for querying specific result subset
	 * - @var integer limit: Maximum number of results to return.
	 * - @var array[string] filters
	 * - @var array[string] fields
	 * - @var boolean search_global
	 * @throws VKClientException
	 * @throws VKApiException
	 * @return mixed
	 */
	public function getHints($access_token, array $params = []) {
		return $this->request->post('search.getHints', $access_token, $params);
	}
}
