<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Actions;

use XTS_BUILD\VK\Client\VKApiRequest;
use XTS_BUILD\VK\Exceptions\Api\VKApiAuthDelayException;
use XTS_BUILD\VK\Exceptions\Api\VKApiAuthFloodException;
use XTS_BUILD\VK\Exceptions\Api\VKApiParamPhoneException;
use XTS_BUILD\VK\Exceptions\Api\VKApiPhoneAlreadyUsedException;
use XTS_BUILD\VK\Exceptions\VKApiException;
use XTS_BUILD\VK\Exceptions\VKClientException;

/**
 */
class Auth {

	/**
	 * @var VKApiRequest
	 */
	private $request;

	/**
	 * Auth constructor.
	 *
	 * @param VKApiRequest $request
	 */
	public function __construct(VKApiRequest $request) {
		$this->request = $request;
	}

	/**
	 * Checks a user's phone number for correctness.
	 *
	 * @param string $access_token
	 * @param array $params 
	 * - @var string phone: Phone number.
	 * - @var integer client_id: User ID.
	 * - @var string client_secret
	 * - @var boolean auth_by_phone
	 * @throws VKClientException
	 * @throws VKApiException
	 * @throws VKApiPhoneAlreadyUsedException This phone number is used by another user
	 * @throws VKApiAuthDelayException Processing.. Try later
	 * @throws VKApiParamPhoneException Invalid phone number
	 * @return mixed
	 */
	public function checkPhone($access_token, array $params = []) {
		return $this->request->post('auth.checkPhone', $access_token, $params);
	}

	/**
	 * Allows to restore account access using a code received via SMS. " This method is only available for apps with [vk.com/dev/auth_direct|Direct authorization] access. "
	 *
	 * @param string $access_token
	 * @param array $params 
	 * - @var string phone: User phone number.
	 * - @var string last_name: User last name.
	 * @throws VKClientException
	 * @throws VKApiException
	 * @throws VKApiAuthFloodException Too many auth attempts, try again later
	 * @return mixed
	 */
	public function restore($access_token, array $params = []) {
		return $this->request->post('auth.restore', $access_token, $params);
	}
}
