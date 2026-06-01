<?php
/**
 * @package   AdminTools
 * @copyright 2010-{2021} Akeeba Ltd / Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class AtsystemFeatureAlloweddomains extends AtsystemFeatureAbstract
{
	protected $loadOrder = 2;

	protected $allowedDomains = [];

	public function isEnabled()
	{
		$rawList = $this->cparams->getValue('allowed_domains');

		$this->setAllowedDomains($rawList);

		return !empty($this->allowedDomains);
	}


	public function onAfterInitialise()
	{
		$uri  = Uri::getInstance();
		$host = $uri->getHost();

		// No host information passed from the server? Can't protect you.
		if (empty($host))
		{
			return;
		}

		// Is the host explicitly allowed
		$host = strtolower($host);

		if (in_array($host, $this->allowedDomains))
		{
			return;
		}

		// Does the host match the live_site variable?
		$lsHost = $this->getLiveSiteHost();

		if (!empty($lsHost) && ($host === $lsHost))
		{
			return;
		}

		// Domains resolving to localhost are always allowed (lets you restore locally)
		$ip = gethostbyname($host);

		if (($ip === '127.0.0.1') || ($ip === '::1'))
		{
			return;
		}

		header('HTTP/1.0 400');
		echo <<< HTML
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>400 Bad Request</title>
</head><body>
<h1>Bad Request</h1>
</body></html>
HTML;

		$this->app->close();
	}

	private function setAllowedDomains(?string $rawList)
	{
		$this->allowedDomains = [];

		$rawList = trim(($rawList ?? ''));

		if (empty($rawList))
		{
			return;
		}

		$domains = explode(',', $rawList);
		$domains = array_map('trim', $domains);
		$domains = array_map('strtolower', $domains);
		$domains = array_filter($domains, function ($x) {
			return !empty($x);
		});

		$extraDomains = array_map(function ($x) {
			if (($x == 'localhost') || (substr($x, -6) === '.local') || (substr($x, -12) === '.localdomain'))
			{
				return '';
			}

			if (substr($x, 0, 4) === 'www.')
			{
				return substr($x, 4);
			}

			return 'www.' . $x;
		}, $domains);

		$domains = array_merge($domains, $extraDomains);
		$domains = array_filter($domains, function ($x) {
			return !empty($x);
		});

		$this->allowedDomains = array_unique($domains);
	}

	private function getLiveSiteHost()
	{
		$live_site = trim(Factory::getApplication()->get('live_site') ?: '');

		if (empty($live_site))
		{
			return null;
		}

		$uri = Uri::getInstance($live_site);

		return strtolower($uri->getHost() ?: '') ?: null;
	}
}