<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$url = Uri::root().'index.php?option=com_rsseo&task=report'; ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=report'); ?>" method="post" name="adminForm" id="adminForm" class="form-horizontal">
	
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		<?php 
			foreach ($this->form->getFieldsets() as $fieldset) {
				$content = '';
				
				if ($fieldset->name == 'cron') {
					$content = '<div class="alert alert-info"><span class="icon-info" aria-hidden="true"></span> '.Text::sprintf('COM_RSSEO_REPORT_CRON_INFO', $url).'</div>';
				}
				
				$this->tabs->addTitle($fieldset->label, $fieldset->name);
				$this->tabs->addContent($content.$this->form->renderFieldset($fieldset->name));
			}
			
			echo $this->tabs->render(); 
		?>
	</div>
	
	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="task" value="" />
</form>