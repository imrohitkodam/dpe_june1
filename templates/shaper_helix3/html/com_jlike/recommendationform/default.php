<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;


JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
$app      = Factory::getApplication();
$menu     = $app->getMenu();
$menuItem = $menu->getItems('link', 'index.php?option=com_jlike&view=recommendations', true);

// DPE Hack start for date format
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params         = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');

$urlData  = new Registry($this->item->params);
$pageLink = $urlData['current_page_link'];

// get the element from the content id 
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
$contentTable = Table::getInstance('Content', 'JlikeTable');
$contentTable->load(array('id' => $this->item->content_id));
$element = $contentTable->element;

// DPE hack end
?>
<div class="row mr-5">
	<div id="backBtn">
		<a class="pull-right fs-16 font-600 cursor-pointer" href="<?php echo Route::_($menuItem->link . '&Itemid=' . $menuItem->id); ?>"><i class="fa fa-arrow-left mr-10" aria-hidden="true"></i><?php echo Text::_('COM_JLIKE_RECOMMENDATION_BACK_BUTTON');?></a>
	</div>
</div>
<fieldset id="users-profile-core" class="trainingrecord-view">
	<h3 class="mt-0 mb-20 pb-20">
		<?php echo Text::_('COM_JLIKE_RECOMMENDATION_DETAIL_VIEW_HEAD'); ?>
	</h3>
	<dl class="dl-horizontal">
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_RECOMMENDATION_TITLE'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->title); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_TODO_DESCRIPTION'); ?>
		</dt>
		<dd>
			<!-- DPE hack added to make links clickable -->
			<?php echo DPE::utilities()->urlToClickableLink($this->escape($this->item->sender_msg)); ?>
		</dd>
		<?php if ($pageLink) { ?>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_PAGE_LINK'); ?>
		</dt>
		<dd>
			<?php echo '<a href="' . htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8') . '</a>'; ?>
		</dd>
		<?php } ?>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_RECOMMENDATION_DUE_DATE'); ?>
		</dt>
		<dd>
			<?php echo HTMLHelper::_('date', $this->escape($this->item->due_date), $dateTimeFormat, false); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_TODO_STATUS'); ?>
		</dt>
		<dd>
			<?php 
			// check the client and for com_tjlms.lesson and com_tjlms.course the complete and incomplete button will hide as that will be auto updated
			if ($this->item->status === 'I' && (($this->item->client != 'com_tjlms.lesson') && ($this->item->client != 'com_tjlms.course')))
			{
				echo Text::_('COM_JLIKE_FORM_TODO_STATUS_INCOMPLETED');
			?>
				<span>
					<a class="complete-mark" href="<?php echo Route::_('index.php?option=com_jlike&' . $tmplComponent . 'task=recommendation.updateTodoStatus&status=C&content_id=' . $this->item->content_id . '&id=' . $this->item->id); ?>" title="<?php echo Text::_('COM_JLIKE_COMPLETE_TODO');?>" class="btn btn-mini" type="button">
					 <?php 
					if (($element != 'com_tjlms.lesson') && ($element != 'com_tjlms.course')){
					 		?>
					<i class="fa fa-circle-o fa-lg"></i>
					<i class="fa fa-check-circle fa-lg"></i>
				<?php } ?>
					</a>
				</span>
			<?php  
			// check the client and for com_tjlms.lesson and com_tjlms.course the complete and incomplete button will hide as that will be auto updated
			}
			elseif ($this->item->status === 'C' && (($this->item->client != 'com_tjlms.lesson') && ($this->item->client != 'com_tjlms.course')))
			{
				echo Text::_('COM_JLIKE_FORM_TODO_STATUS_COMPLETED');
			?>
				<span>
					<a class="incomplete-mark" href="<?php echo Route::_('index.php?option=com_jlike&' . $tmplComponent . 'task=recommendation.updateTodoStatus&status=I&content_id=' . $this->item->content_id . '&id=' . $this->item->id); ?>" title="<?php echo Text::_('COM_JLIKE_INCOMPLETE_TODO');?>" class="btn btn-mini" type="button"
					>
					 <?php 




					 		if (($element != 'com_tjlms.lesson') && ($element != 'com_tjlms.course')){
					 		?>
					 		<i class="fa fa-check-circle fa-lg"></i>
							<i class="fa fa-times-circle fa-lg"></i>
						
					<?php }?>
						
					</a>
				</span>
			<?php
			}
			?>
		</dd>
		<?php 
		if ($this->item->done_date != "0000-00-00 00:00:00" && $this->item->status === 'C')
		{
		?>
			<dt>
				<?php echo Text::_('COM_JLIKE_FORM_LBL_DONE_DATE_TITLE'); ?>
			</dt>
			<dd>
				<?php echo HTMLHelper::_('date', $this->escape($this->item->done_date), $dateTimeFormat, false); ?>
			</dd>
		<?php
		}
		?>
	</dl>
</fieldset>
