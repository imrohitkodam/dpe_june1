<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldAvatars extends JFormFieldList
{
	protected $type = 'Avatars';
	
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
		
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);

		$components = array(
			'com_comprofiler',
			'com_community',
			'com_kunena',
		);
		
		$query->select('element')
			  ->from('#__extensions')
			  ->where($db->qn('type').'='.$db->q('component'))
			  ->where($db->qn('element').' IN (' . implode(',', $db->q($components)) . ')');
		$available = $db->setQuery($query)->loadColumn();
		
		$options[] = JHtml::_('select.option', '', JText::_('RST_NO_AVATARS_COMPONENT'));
		$options[] = JHtml::_('select.option', 'gravatar', JText::_('RST_GRAVATAR'));
		
		foreach ($components as $component)
		{
			$disabled = !in_array($component, $available);
			$options[] = JHtml::_('select.option', substr($component, 4), JText::_('RST_' . substr($component, 4)), 'value', 'text', $disabled);
		}
		
		reset($options);
		
		return $options;
	}
}
