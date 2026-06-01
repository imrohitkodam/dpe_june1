<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_tjlms
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;


$document = Factory::getDocument();

$this->filter_order     = $this->escape($this->state->get('list.ordering'));
$this->filter_order_Dir = $this->escape($this->state->get('list.direction'));

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
?>

<div id="tjlms_lessons_modal" class="existing-lessons-modal">
    <form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonsmodal&layout=default&tmpl=component&cid=' . (int) $this->cid . '&mid=' . (int) $this->mid); ?>"
          method="post" name="adminForm" id="adminForm">


    <input type="hidden" name="filter_search" value="<?php echo $search; ?>" />
        <div class="top-heading pickQuesalign">
            <h2 class="componentheading">
                <?php echo Text::_('COM_TJLMS_ADD_EXISTING_LESSON'); ?>
            </h2>

            <?php
            // Search tools bar (this will auto-fill filter_search + filters + limit)
            echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
            ?>
        </div>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-no-items">
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered my-3">
                    <thead>
                        <tr>
                            <th width="5%">
                                <?php echo HTMLHelper::_('grid.sort', Text::_('JGRID_HEADING_ID'), 'l.id', $this->filter_order_Dir, $this->filter_order); ?>
                            </th>
                            <th>
                                <?php echo HTMLHelper::_('grid.sort', Text::_('COM_TJLMS_LESSON_NAME'), 'l.title', $this->filter_order_Dir, $this->filter_order); ?>
                            </th>
                            <th width="15%">
                                <?php echo HTMLHelper::_('grid.sort', Text::_('COM_TJLMS_LESSON_FORMAT'), 'l.format', $this->filter_order_Dir, $this->filter_order); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr>
                                <td><?php echo (int) $item->id; ?></td>
                                <td>
                                <a href="javascript:void(0);"
                                onclick="tmt.tests.addLessonToCourse(<?php echo (int) $item->id; ?>, <?php echo (int) $this->cid; ?>,<?php echo (int) $this->mid; ?>)"
                                class="lesson-select">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                </td>
                                <td><?php echo htmlspecialchars($item->format, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
