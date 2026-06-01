<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');

$document=Factory::getDocument();
$document->addStylesheet(Uri::root().'components/com_tmt/assets/css/tmt.css');

$this->filter_order = $this->escape($this->state->get('list.ordering'));
$this->filter_order_Dir = $this->escape($this->state->get('list.direction'));

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
?>

<div id="tmt_tests" class="row-fluid existing-tests-modal">
	<form action="<?php echo Route::_('index.php?option=com_tmt&view=tests&layout=modal&tmpl=component&cid=' . $this->cid . "&mid=" . $this->mid); ?>" method="post" name="adminForm" id="adminForm">

		<div class="top-heading pickQuesalign">

			<!-- set componentheading -->
			<h2 class="componentheading">
					<?php echo Text::_('COM_TMT_ADDTOLESSON_TESTS_PAGE_HEADING');?>
			</h2>
			<?php
			// Search tools bar
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>

		</div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>

			<div class="row-fluid">
				<table class="category table table-striped table-bordered table-hover">

					<thead>
						<tr>
							<th>
								<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_TITLE'), 'title', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>

							<th class="center hidden-phone com_tmt_width5 nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_CREATED_ON'), 'a.created_on', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>

							<th class="nowrap center com_tmt_width5">
								<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_MARKS'), 'total_marks', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap center com_tmt_width5">
								<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_TIME'), 'time_duration', $this->filter_order_Dir, $this->filter_order ); ?>

							</th>
							<th class="nowrap center com_tmt_width5">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_TEST_QUES', 'questions_count', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $row)
						{
							?>
								<tr>
									<td>
										<input type="hidden" class="quiz_id" name="quiz_id" value="<?php echo trim($row->id) ?>" />
										<a href="javascript:void(0)" onclick="tmt.tests.addToCourse('<?php echo $row->id;;?>', '<?php echo $this->cid;?>', '<?php echo $this->mid;?>');"title="<?php Text::_('COM_TMT_ASSIGN_TEST_TO_LESSON_ONHOVER') ;  ?>">
											<?php echo $row->title; ?>
										</a>
									</td>

									<td class="center small nowrap hidden-phone com_tmt_width5">
										<span class="badge badge-info">
											<?php echo $row->created_on;?>
										</span>
									</td>

									<td class="small center nowrap com_tmt_width5" >
										<?php echo $row->total_marks; ?>
									</td>


									<td class="small center nowrap com_tmt_width5" >
										<?php echo Text::sprintf('COM_TMT_TEST_TIME_TEXT', $row->time_duration); ?>
									</td>
									<td class="small center nowrap com_tmt_width5" >
										<?php echo $row->questions_count; ?>
									</td>


								</tr>
				<?php }//end for?>
					</tbody>
				</table>
			</div><!--row-fluid-->
		<?php endif; ?>
			<div class="row-fluid">
					<div class="pagination">
						<?php echo $this->pagination->getListFooter(); ?>
					</div>
					<hr class="hr hr-condensed"/>
			</div><!--row-fluid-->

			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="<?php echo Session::getFormToken();?>" value="1" data-js-id="form-token"/>
		</form>
</div><!--row-fluid-->

