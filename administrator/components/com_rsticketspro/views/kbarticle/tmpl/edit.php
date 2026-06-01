<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

// Load JavaScript message titles
JText::script('ERROR');
JText::script('WARNING');
JText::script('NOTICE');
JText::script('MESSAGE');

JHtml::_('stylesheet', 'com_rsticketspro/rsticketspro-admin.css', array('relative' => true, 'version' => 'auto'));
if ($this->ticket)
{
	echo '<a href="'.JRoute::_('index.php?option=com_rsticketspro&view=ticket&id='.$this->ticket->id).'">'.JText::sprintf('RST_KB_ARTICLE_CONVERTED_FROM', $this->ticket->subject, $this->ticket->code).'</a>';
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=kbarticle&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal rst-d-flex" enctype="multipart/form-data">
	<?php
	if ($this->item->thumb && $this->item->id)
	{
		echo '<div class="rst-thumbnail">' . JHtml::_('image', 'components/com_rsticketspro/assets/thumbs/articles/' . $this->item->thumb, '', array('class' => (version_compare(JVERSION, '4.0', '>=') ? 'img-' : '') . 'thumbnail'), false) . '</div>';
	}
	else
	{
		$this->form->setFieldAttribute('delete_thumb', 'disabled', 'true');
		$this->form->setFieldAttribute('delete_thumb', 'filter', 'unset');
	}
	?>
	<div class="rst-form-fields">
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}
	?>
	</div>
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</div>
</form>