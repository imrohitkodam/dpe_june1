<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2022 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use GSD\MappingOptions;
use NRFramework\Functions;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

require 'app/Shared.php';
require 'app/Company.php';
require 'app/Event.php';
require 'app/Offer.php';

/**
 *   Google Structured Data Plugin
 */
class plgGSDJBusinessDirectory extends Shared
{
	use Company;
	use Event;
	use Offer;
	
    /**
     * Indicates the query string parameter name that is used by the front-end component
     *
     * @var  string
     */
    protected $thingRequestIDName = 'companyId';

	/**
	 * Currently manipulating item.
	 * 
	 * @var  object
	 */
	protected $item;
	
	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);
		
		if (!class_exists('JBusinessUtil'))
		{
			$class_file = JPATH_SITE . '/components/com_jbusinessdirectory/helpers/utils.php';

			if (!class_exists($class_file))
			{
				return;
			}

        	require_once $class_file;
		}
	}
	
    /**
	 * The MapOptions Backend Event.
	 * 
	 * Triggered by the MappingOptions fields to help each integration add its own map options.
	 *  
	 * @param	string	$plugin
	 * @param	array	$options
	 *
	 * @return	void
	 */
    public function onMapOptions($plugin, &$options)
    {
		if ($plugin != $this->_name)
        {
			return;
		}
		
		// Remove the following options
		$remove_options = [
			'metakey',
			'metadesc'
		];
		
		// Remove unsupported mapping options
		foreach ($remove_options as $key => $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}

		// Load J-BusinessDirectory Tables
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jbusinessdirectory/tables');

		// Load J-BusinessDirectory Backend Languages
		if (class_exists('JBusinessUtil'))
		{
			JBusinessUtil::loadAdminLanguage();
		}

		// Add Shared Fields
		if ($fields = $this->getSharedFields())
		{
			MappingOptions::add($options, $fields, 'PLG_GSD_JBUSINESSDIRECTORY_SHARED_FIELDS', 'gsd.item.shared.');
        }

		// We require an app view to attach its' mapping options
		if (empty($this->appview))
		{
			return;
		}

		/**
		 * Add each J-BusinessDirectory listing main fields as well as custom fields.
		 * 
		 * Array format:
		 *  App View => [
		 * 		'status' => Attribute Type,
		 *		'type' => Listing Name
		 *  ]
		 * 
		 * Attribute Type: is a key that dintinguishes one listing from another and is
		 * used by the J-BusinessDirectory component to fetch data related to this listing
		 * (i.e. all of its custom attributes, its reviews, etc...).
		 * 
		 * Listing Name: is a name included in internal methods to load the fields per app view
		 */
		$listings = [
			'companies' => [
				'status' => 1,
				'type' => 'company'
			],
			'event' => [
				'status' => 3,
				'type' => 'event'
			],
			'offer' => [
				'status' => 2,
				'type' => 'offer'
			]
		];

		// Ensure valid appview is given
		if (!isset($listings[$this->appview]))
		{
			return;
		}

		$status = $listings[$this->appview]['status'];
		$type = $listings[$this->appview]['type'];
		
		$mainFieldsFunction = 'get' . ucfirst($type) . 'Fields';
		
		// Add Main Fields
		if ($fields = $this->$mainFieldsFunction())
		{
			MappingOptions::add($options, $fields, 'PLG_GSD_JBUSINESSDIRECTORY_' . strtoupper($type) . '_FIELDS', 'gsd.item.' . $type . '.');
		}
		
		// Add Custom Fields
		if ($fields = $this->getItemCustomFields($status))
		{
			MappingOptions::add($options, $fields, 'PLG_GSD_JBUSINESSDIRECTORY_' . strtoupper($type) . '_CUSTOM_ATTRIBUTES', 'gsd.item.' . $type . '.cf.');
		}
	}
}