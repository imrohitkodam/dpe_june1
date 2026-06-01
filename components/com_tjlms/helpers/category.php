<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Categories\Categories;

jimport('joomla.application.categories');


/**
 * Tjlms Categories helper.
 *
 * @since  1.0.0
 */
class TjlmsCategories extends Categories
{
	/**
	 * Method acts as a consturctor
	 *
	 * @param   ARRAY  $options  categories with parent
	 *
	 * @since   1.0.0
	 */
	public function __construct($options = array())
	{
		$options['table'] = '#__categories';
		$options['extension'] = 'com_tjlms';
		parent::__construct($options);
	}
}
