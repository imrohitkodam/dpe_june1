<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Table\Table;

/**
 * Cluster table class
 *
 * @since  1.0.0
 */
class ClusterTableClusters extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_clusters', 'id', $db);

		// Set the alias since the column is called state
		$this->setColumnAlias('published', 'state');
	}
}
