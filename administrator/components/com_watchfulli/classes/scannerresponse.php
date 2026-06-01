<?php
/**
 * @version     admin/classes/scannerresponse.php 2020-05-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */
defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliScannerResponse
{
	public function getResults($values, $error)
	{
		$rep         = new stdClass();
		$rep->error  = $error;
		$rep->values = $values;

		return $rep;
	}

	public function sendOk($value = null)
	{
		return $this->getResults($value, 0);
	}

	public function sendKo($value = null)
	{
		return $this->getResults($value, 1);
	}
}
