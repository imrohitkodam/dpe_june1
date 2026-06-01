<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

HTMLHelper::_('formbehavior.chosen', '.multipleFrameworks', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT')));

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.lft';

$doc = Factory::getDocument();

$doc->addStyleDeclaration('
	.chzn-container-multi a.search-choice-close {
		height: 12px !important;
	}'
);

?>

<div class="tj-page tjBs3 container-fluid">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=skills'); ?>" method="post" name="adminForm" id="adminForm">
			<div class="tj-search-filters">
			<?php
			// Search tools bar
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>
			</div>

			<div class="clearfix"></div>
			<?php
			if (empty($this->items))
			{
			?>
				<div class="alert alert-info alert-no-items ">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php
			}
			else
			{

				?>
				<div class="row">

					<?php
					foreach ($this->items as $i => $item)
					{
					?>
					  <div class="col-sm-6 col-md-4">
					    <div class="thumbnail">
					    <a href="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillcontentmaps&filter[skill_id]=' . $item->id, false); ?>">
					      <!-- <img src="..." alt="..."> -->
					      <div class="caption">
					        <h5>
					        	<span class="label label-primary"><?php echo $this->escape(ucfirst($item->title)); ?></span>
					        </h5>

							<p>
								<?php
								if (ComponentHelper::isEnabled('com_tjlms'))
								{
									$courseContentCount = TjCompetency::SkillContentMap()::getContentCount($item->id, 'course');

									if ($courseContentCount)
									{
									?>
										<span class="label label-info"><?php echo $courseContentCount; ?></span> Elearning Course(s)
									<?php
									}
								}
								?>
								<?php
								if (ComponentHelper::isEnabled('com_jticketing'))
								{
									$eventContentCount = TjCompetency::SkillContentMap()::getContentCount($item->id, 'event');

									if ($eventContentCount)
									{
									?>
									<p></p>
									<span class="label label-info"><?php echo $eventContentCount; ?></span> Classroom Session(s)
									<?php
									}
								}
								?>
							</p>
					      </div>
					    </a>
					    </div>
					  </div>
					<?php
					}
					?>
				</div>
			<?php
			}
			?>
			<div class="pager">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</div>
