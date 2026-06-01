<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access to this file
defined('_JEXEC') or die;

// Import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


/**
 * Class formField advanced search
 *
 * @since  3.3
 */
class JFormFieldAdvsearch extends JFormFieldList
{
		/**
		 * The field type.
		 *
		 * @var         string
		 */
		protected $type = 'Advsearch';

		/**
		 * Method to get a list of options for a list input.
		 *
		 * @return      array           An array of JHtml options.
		 */
		protected function getOptions()
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__advanced_search_indexer');
			$db->setQuery((string) $query);
			$messages = $db->loadObjectList();
			$options = array();

			if ($messages)
			{
					foreach ($messages as $message)
					{
							$options[] = JHtml::_('select.option', $message->type_name, $message->name);
					}
			}

			$options = array_merge(parent::getOptions(), $options);

			return $options;
		}
}
