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

class JFormFieldRSTags extends JFormFieldTag
{
	public $type = 'RSTags';
	
	public function __construct($form = null)
	{
		parent::__construct($form);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';
		
		if (version_compare(JVERSION, '4.0', '>=')) {
			JFactory::getDocument()->getWebAssetManager()->usePreset('choicesjs')->useScript('webcomponent.field-fancy-select');
		} else {
			JHtml::_('formbehavior.chosen', '.rst-tags', null, array('placeholder_text_multiple' => JText::_('JGLOBAL_TYPE_OR_SELECT_SOME_TAGS')));
		}
	}
	
    protected function getInput()
    {
		
		$all_options		= $this->getOptions();
		$selected_options	= $this->getOptions(true);
		$select_options		= array();
		
		if (version_compare(JVERSION, '4.0', '>=')) {
			$select_attr	= '';
			$fancy_attr		= '';
			
			$select_attr .= !empty($this->element['size']) ? ' size="' . $this->element['size'] . '"' : '';
			$select_attr .= $this->element['multiple'] ? ' multiple' : '';
			
			$fancy_attr .= !empty($this->element['class']) ? ' class="' . $this->element['class'] . '"' : '';
			$fancy_attr .= ' placeholder="' . JText::_('JGLOBAL_TYPE_OR_SELECT_SOME_TAGS',true) . '" ';
			$fancy_attr .= ' allow-custom';
			
			foreach ($all_options as $option) {
				$select_options[] = JHTML::_('select.option', $option, $option);
			}
			
			$select = JHTML::_('select.genericlist', $select_options, $this->name, trim($select_attr), 'value', 'text', $selected_options);
			
			$html[] = '<joomla-field-fancy-select ' . trim($fancy_attr) . '>' . $select .'</joomla-field-fancy-select>';
			$html[] = '<input name="rstags[]" type="hidden" value=""/>';
			
			return implode($html);
		} else {
			\JHtml::_('tag.ajaxfield', '#jform' . $this->element['name'], true);
			
			$select_attr	= '';
			
			$select_attr .= !empty($this->element['size']) ? ' size="' . $this->element['size'] . '"' : '';
			$select_attr .= $this->element['multiple'] ? ' multiple' : '';
			$select_attr .= !empty($this->element['class']) ? ' class="' . $this->element['class'] . '"' : '';

			foreach ($all_options as $option) {
				$select_options[] = JHTML::_('select.option', $option, $option);
			}
			
			$select = JHTML::_('select.genericlist', $select_options, $this->name, trim($select_attr), 'value', 'text', $selected_options);
			
			return $select;
		}
    }
	
	protected function getOptions($slected = false)
	{
		$db 	= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query	->select('DISTINCT(' . $db->qn('tag') . ')')
				->from($db->qn('#__rsticketspro_kb_content_tags'));
		
		if ($slected) {
			$id = JFactory::getApplication()->input->getInt('id', 0);
			$query->where($db->qn('article_id') . '=' . $db->q($id));
		}
		
		$query->order($db->qn('id') . ' ASC');
		$db->setQuery($query);
		
		$options = $db->loadObjectList();

		if (count($options)) {
			foreach ($options as $key => $option) {
				$options[$key] = $option->tag;
				unset($option->tag);
			}
		}

		return $options;
	}
}