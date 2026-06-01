<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class JFormFieldKbarticle extends JFormField
{
	protected function getInput()
	{
		$html = array();
		// Include our JTable
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/tables');
		$row = JTable::getInstance('Kbcontent', 'RsticketsproTable');

		// Load the title
		$title = JText::_('RST_KB_SELECT_ARTICLE');
		
		if ($this->value && $row->load($this->value))
		{
			$title = $row->name;
		}
		
		// Include jQuery
		JHtml::_('jquery.framework');
		
		// URL to article list
		$link = 'index.php?option=com_rsticketspro&view=kbarticles&layout=element&tmpl=component';

		$js = "
		function elSelectEvent(id, title) {
			document.getElementById('".$this->id."').value = title;
			document.getElementsByName('".$this->name."')[0].value = id;
			jQuery('#rsticketsproKBArticleModal').modal('hide');
		}";

		JFactory::getDocument()->addScriptDeclaration($js);
		
		$html[] = '<span class="input-group input-append"><input type="text" class="input-medium form-control" required="required" readonly="readonly" id="' . $this->id
			. '" value="' . htmlspecialchars($title, ENT_COMPAT, 'utf-8') . '" />';
		$html[] = '<a href="#rsticketsproKBArticleModal" role="button" class="btn btn-secondary" data-bs-toggle="modal" data-toggle="modal" title="' . JText::_('JSELECT') . '">'
			. '<span class="icon-file" aria-hidden="true"></span> '
			. JText::_('JSELECT') . '</a></span>';
		$html[] = JHtml::_(
			'bootstrap.renderModal',
			'rsticketsproKBArticleModal',
			array(
				'url'        => $link,
				'title'      => JText::_('RST_KB_SELECT_ARTICLE'),
				'width'      => '800px',
				'height'     => '300px',
				'modalWidth' => '80',
				'bodyHeight' => '70',
				'footer'     => '<a type="button" class="btn" data-bs-dismiss="modal" data-dismiss="modal" aria-hidden="true">'
						. JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>'
			)
		);
		$html[] = '<input class="input-small" type="hidden" name="' . $this->name . '" value="'
			. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" />';

		return implode("\n", $html);
	}
}