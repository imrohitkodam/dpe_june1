<?php
/**
 * @package    RSTicketsPro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 * @link       https://www.rsjoomla.com
 */

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldRsticketstatus extends \JFormFieldList
{
	protected $type = 'rsticketstatus';

	/**
	 * Field to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options   = array();
		//$options[] = HTMLHelper::_('select.option', "", Text::_('COM_DPE_MY_TICKETS_FILTER_STATUS_DEFAULT'));

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'name')));
		$query->from($db->quoteName('#__rsticketspro_statuses'));
		$query->where($db->quoteName('published') . '=' . $db->q(1));
		$query->order($db->quoteName('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		$statuses = $db->loadObjectList();

		if (!empty($statuses))
		{
			foreach ($statuses as $status)
			{
				$options[] = HTMLHelper::_('select.option', $status->id, $status->name);
			}
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
