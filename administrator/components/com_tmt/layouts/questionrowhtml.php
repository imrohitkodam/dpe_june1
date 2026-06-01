<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$data        = $displayData;
$showQueSort = '';

if (!$displayData['canDeleteQ'] || $displayData['type'] == 'set')
{
	$showQueSort = "hide";
}

switch ($data['type'])
{
	case "radio":
		$data['type'] = Text::_('COM_TMT_QTYPE_MCQ_SINGLE_SHORT');
	break;
	case "checkbox":
		$data['type'] = Text::_('COM_TMT_QTYPE_MCQ_MULTIPLE_SHORT');
	break;
	case "file_upload":
		$data['type'] = Text::_('COM_TMT_QTYPE_FILE_UPLOAD');
	break;
	case "text":
		$data['type'] = Text::_('COM_TMT_QTYPE_SUB_TEXT');
	break;
	case "textarea":
		$data['type'] = Text::_('COM_TMT_QTYPE_SUB_TEXTAREA');
	break;
	case "objtext":
		$data['type'] = Text::_('COM_TMT_QTYPE_OBJ_TEXT');
	break;
	default:
		$data['type'] = $data['type'];
}
?>
<!--section-question-->
<div class="section_question py-10" id="section_question_<?php echo $data['id'];?>" data-js-itemid="<?php echo $data['id'];?>" data-js-id="section-question">
<!--row-fluid-->
<div class="row">
	<!--col-md-6-->
	<div class="col-md-4">
		<div class="d-flex">
			<input onclick="Joomla.isChecked(this.checked)" type='checkbox' id='cb<?php echo $data['id']?>' name='cid[]' value='<?php echo $data['id']?>' style="display: none;" checked>
			 <col-md- class="sortable-handler <?php echo $showQueSort;?>" id="reorder" title="<?php echo Text::_('COM_TMT_TEST_FORM_REORDER');?>" style="cursor: move;">
				<i class="icon-menu valign-middle"></i>
			</col-md->
			<col-md->
				<?php $link = Route::_("index.php?option=com_tmt&view=question&layout=edit&id=" . $data['id'] . (isset($this->addquiz) ? "&addquiz=1" : "" ) . "&tmpl=component&gradingtype=" . $data['gradingtype']  . "&target=section"); ?>
				<a href="javascript:void(0);" onclick="tmt.section.openQuestionPopups(this, 'editQuestionModal'); jQuery('#editQuestionModal').removeClass('hide')">
					<?php echo htmlentities($data['title']);?>
				</a>
			</col-md->
		</div>
	</div><!--/col-md-6-->
	<div class="col-md-7">
		<div class="row">
		<?php if ($data['gradingtype'] == 'quiz') : ?>
			<div class="col-md-3"><?php echo Text::_('COM_TMT_TEST_FORM_MARKS'). ": "; ?><col-md- data-js-id="marks"><?php echo $data['marks'];?></col-md-></div>
		<?php endif; ?>
			<div class="col-md-3"><?php echo Text::_('COM_TMT_TEST_FORM_CATEGORY'). ": "; ?><col-md-><?php echo $this->escape($data['cat_title']);?></col-md-></div>
			<div class="col-md-3"><?php echo Text::_('COM_TMT_TEST_FORM_TYPE') . ": "; ?><col-md-><?php echo $this->escape($data['type']);?></col-md-></div>
			<div class="col-md-3"><?php echo Text::_('COM_TMT_TEST_FORM_LEVEL') . ": "; ?><col-md-><?php echo $this->escape(ucfirst($data['level']));?></col-md-></div>
		<?php if ($data['gradingtype'] != 'quiz') : ?>
			<div class="col-md-3"><?php echo Text::_('COM_TMT_TEST_FORM_FIELD_COMPULSORY_QUESTION') . ": "; ?>
				<input type="checkbox" data-js-id="compulsory-question" id="required<?php echo $data['id']; ?>" <?php if(isset($data['is_compulsory']) && $data['is_compulsory'] == 1) echo 'checked'; ?> >
			</div>
		<?php endif; ?>


		</div>
	</div><!--/col-md-5-->
	<!--col-md-1-->
	<div class="col-md-1">
		<col-md- data-js-id="delete-question" class="btn btn-small <?php echo ($data['canDeleteQ']) ? '' : 'disabled';?>" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE');?>" <?php echo ($data['canDeleteQ']) ? '' : 'disabled style="pointer-events: none;"';?>>
			<i class="icon-trash"> </i>
		</col-md->
	</div><!--/col-md-1--> 
	<?php
	
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'editQuestionModal',
		array(
			'url'        => $link,
			'width'      => '800px',
			'height'     => '300px',
			'modalWidth' => '80',
			'bodyHeight' => '70'
		)
	); 
	?>
</div><!--/row-fluid-->
</div><!--/section-question-end-->


