<?php
/**
 * @package     RAD
 * @subpackage  Event
 *
 * @copyright   Copyright (C) 2015 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class RADEventViewBeforedisplay extends RADEventBase
{
	protected $requiredArguments = ['view', 'context'];

	/**
	 *
	 * @return RADViewHtml
	 */
	public function getView(): RADViewHtml
	{
		return $this->getArgument('view');
	}
}