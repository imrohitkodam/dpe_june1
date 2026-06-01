<?php
/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Version information class for the SYW Library
 */
class JForceVersion
{
	/** @var  string  Product name. */
	static $PRODUCT = 'JForce Library';

	/** @var  string  Release version. */
	static $RELEASE = '1.1.0';

	/** @var  string  Release date. */
	static $RELDATE = '04-February-2014';

	/** @var  string  Copyright Notice. */
	static $COPYRIGHT = 'Copyright (C) 2014 JoomlaForce Team. All rights reserved.';

	/** @var  string  Link text. */
	static $URL = '<a href="http://www.joomlaforce.com">joomlaforce.com</a>.';

	/**
	 * Compares two a "PHP standardized" version number against the current library version.
	 *
	 * @param   string  $minimum  The minimum version of the Joomla which is compatible.
	 * @return  bool    True if the version is compatible.
	 * @see     http://www.php.net/version_compare
	 */
	static function isCompatible($minimum)
	{
		return version_compare(self::$RELEASE, $minimum, 'ge');
	}

	/**
	 * Gets a "PHP standardized" version string for the current library.
	 *
	 * @return  string  Version string.
	 */
	static function getVersion()
	{
		return self::$RELEASE;
	}
	
}
