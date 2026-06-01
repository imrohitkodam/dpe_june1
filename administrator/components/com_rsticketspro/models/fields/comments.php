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

class JFormFieldComments extends JFormFieldList
{
	protected $type = 'Comments';
	
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
		
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);

		$components = array(
			'RSComments!' => 'com_rscomments',
			'JComments' => 'com_jcomments',
			'JomComment' => 'com_jomcomment'
		);

		$query->select('element')
			->from('#__extensions')
			->where($db->qn('type').'='.$db->q('component'))
			->where($db->qn('element').' IN (' . implode(',', $db->q($components)) . ')');
		$available = $db->setQuery($query)->loadColumn();
		
		$options[] = JHtml::_('select.option', '0', JText::_('RST_KB_COMMENTS_DISABLED'));
		$options[] = JHtml::_('select.option', 'facebook', JText::_('RST_FACEBOOK_COMMENTS'));
		
		foreach ($components as $name => $component)
		{
			$disabled = !in_array($component, $available);
			$options[] = JHtml::_('select.option', $component, $name, 'value', 'text', $disabled);
		}
		
		reset($options);
		
		return $options;
	}
}
