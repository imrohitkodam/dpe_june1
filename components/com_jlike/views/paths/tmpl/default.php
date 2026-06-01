<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

// Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_jlike', JPATH_SITE);

$document = JFactory::getDocument();
$document->addScript(JURI::base() . 'components/com_jomlike/assets/javascript/jquery-1.8.0.min.js');
$document->addStyleSheet(JURI::base() . 'components/com_jomlike/assets/css/jomlike.css');

// Load bootstrap
$document->addStyleSheet(JURI::base() . 'components/com_jomlike/bootstrap/css/bootstrap.min.css');
$document->addScript(JURI::base() . 'components/com_jomlike/bootstrap/js/bootstrap.min.js');
JHtml::script(JUri::root() . 'components/com_jlike/assets/scripts/jlikepaths.js');

$menu   = $this->app->getMenu();
$menuItem = $menu->getItems('link', 'index.php?option=com_jlike&view=pathusers', true);
?>
<div>
	<form action="" method="post" name="adminForm" id="adminForm">
		<div class="col-xs-12 margint10">
			<div class="row">
				<div class="col-xs-12">
				<hr />
					<div class="page-header">
						<h1><?php echo JText::_('COM_JLIKE_VIEW_PATHWAYS');?></h1>
					</div>
				</div>
				<div class ="col-xs-12 col-md-3">
					<strong><?php echo JText::_('COM_JLIKE_PATHWAY_PATH_TITLE'); ?></strong>
				</div>
				<div class ="col-xs-12 col-md-4">
					<strong><?php echo JText::_('COM_JLIKE_PATHWAY_PATH_DESCRIPTION'); ?></strong>
				</div>
				<div class ="col-xs-12 col-md-3">
					<strong><?php echo JText::_('COM_JLIKE_PATHWAY_PATH_DETAIL_VIEW'); ?></strong>
				</div>
				<div class ="col-xs-12 col-md-2">
					<?php echo JText::_('COM_JLIKE_PATHWAY_PATH_SUBSCRIBE'); ?>
				</div>
			</div>
		</div>
		<?php
		foreach ($this->items as $item)
		{
			?>
			<div class="row">
				<div class="col-xs-12 col-md-3">
					<?php echo htmlspecialchars($item->path_title); ?>
				</div>
				<div class="col-xs-12 col-md-6">
					<?php echo $item->path_description; ?>
				</div>
				<?php
				if ($item->isSubscribedPath)
				{
					if ($item->isPathOfPaths)
					{
						?>
						<div class="col-xs-12 col-md-3">
							<a href="<?php echo JRoute::_("index.php?option=com_jlike&view=pathdetail&path_id=".$item->path_id."&Itemid=".$menuItem->id); ?>"  name="launch_path_<?php echo $item->path_id;?>" id="launch_path_<?php echo $item->path_id;?>" class="btn btn-primary">
								<?php echo JText::_('COM_JLIKE_PATHWAY_PATH_LAUNCH'); ?>
							</a>
						</div>
						<?php
					}
					else
					{
						?>
						<div class="col-xs-12 col-md-3">
							<a href="<?php echo JRoute::_("index.php?option=com_jlike&view=todos&path_id=".$item->path_id."&Itemid=".$menuItem->id); ?>"  name="launch_path_<?php echo $item->path_id;?>" id="launch_path_<?php echo $item->path_id;?>" class="btn btn-primary">
								<?php echo JText::_('COM_JLIKE_PATHWAY_PATH_LAUNCH'); ?>
							</a>
						</div>
						<?php
					}
				}
				else
				{
					?>
					<div class="col-xs-12 col-md-3">
						<a href="index.php?option=com_jlike&task=pathuser.save&path_id=<?php echo $item->path_id;?>" name="subcribe_path_<?php echo $item->path_id;?>" id="subcribe_path_<?php echo $item->path_id;?>" class="btn btn-primary" >
							<?php echo JText::_('COM_JLIKE_PATHWAY_PATH_SUBSCRIBE_BUTTON')?>
						</a>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>
		<input type="hidden" name="task" value="subscription.save"/>
	</form>
</div>
