<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
namespace XTS_BUILD\VK\Actions;

use XTS_BUILD\VK\Actions\Enums\AppWidgetsType;
use XTS_BUILD\VK\Client\VKApiRequest;
use XTS_BUILD\VK\Exceptions\Api\VKApiBlockedException;
use XTS_BUILD\VK\Exceptions\Api\VKApiCompileException;
use XTS_BUILD\VK\Exceptions\Api\VKApiParamGroupIdException;
use XTS_BUILD\VK\Exceptions\Api\VKApiRuntimeException;
use XTS_BUILD\VK\Exceptions\Api\VKApiWallAccessPostException;
use XTS_BUILD\VK\Exceptions\Api\VKApiWallAccessRepliesException;
use XTS_BUILD\VK\Exceptions\VKApiException;
use XTS_BUILD\VK\Exceptions\VKClientException;

/**
 */
class AppWidgets {

	/**
	 * @var VKApiRequest
	 */
	private $request;

	/**
	 * AppWidgets constructor.
	 *
	 * @param VKApiRequest $request
	 */
	public function __construct(VKApiRequest $request) {
		$this->request = $request;
	}

	/**
	 * Allows to update community app widget
	 *
	 * @param string $access_token
	 * @param array $params 
	 * - @var string code
	 * - @var AppWidgetsType type
	 * @throws VKClientException
	 * @throws VKApiException
	 * @throws VKApiCompileException Unable to compile code
	 * @throws VKApiRuntimeException Runtime error occurred during code invocation
	 * @throws VKApiBlockedException Content blocked
	 * @throws VKApiWallAccessPostException Access to wall's post denied
	 * @throws VKApiWallAccessRepliesException Access to post comments denied
	 * @throws VKApiParamGroupIdException Invalid group id
	 * @return mixed
	 */
	public function update($access_token, array $params = []) {
		return $this->request->post('appWidgets.update', $access_token, $params);
	}
}
