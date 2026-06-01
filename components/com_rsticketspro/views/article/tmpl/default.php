<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

if ($this->params->get('show_page_heading', 1))
{
	?>
	<h1 class="rst-heading"><?php echo ($this->article->is_draft && RSTicketsProHelper::isStaff() ? '[' . JText::_('RST_KB_ARTICLE_DRAFT') . '] ' : '') . (!empty($this->article->name) ? $this->escape($this->article->name) : $this->escape($this->params->get('page_heading'))); ?></h1>
	<?php
}

if (RSTicketsProHelper::isStaff()) {
	?>
	<div class="rst-article-buttons">
	<?php if ($this->article->draft) { ?>
		<a class="btn btn-secondary" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=article&cid='.RSTicketsProHelper::KbSEF($this->article).($this->article->is_draft ? '' : '&draft=1')); ?>"><?php echo ($this->article->is_draft ? '<span class="rsticketsproicon-arrow-left"></span>' . JText::_('RST_KB_ARTICLE_DRAFT_BACK') : '<span class="rsticketsproicon-doc-text"></span>' . JText::_('RST_KB_ARTICLE_VIEW_DRAFT')); ?></a>
	<?php } else { ?>
		<div class="alert alert-info">
		<?php echo JText::_('RST_KB_ARTICLE_NO_DRAFT'); ?>
		</div>
	<?php } ?>
	</div>
	<?php
}

$article_content = ($this->article->is_draft && RSTicketsProHelper::isStaff()) ? $this->article->draft : $this->article->text;
?>
<div class="rst-article-content">
<?php
echo RSTicketsProHelper::getConfig('kb_load_plugin') ? JHtml::_('content.prepare', $article_content, null, 'com_rsticketspro.article') : $article_content;

if (RSTicketsProHelper::getConfig('kb_feedback_section')) {
?>
	<div class="rst-kb-feedback">
		<?php if ($this->article->positive_feedback > 0) { ?>
		<div class="rst-kb-helpful">
			<p><?php echo $this->article->positive_feedback > 1 ? JText::sprintf('RST_KB_ARTICLE_FEEDBACK_HELPED_USERS', $this->article->positive_feedback) : JText::_('RST_KB_ARTICLE_FEEDBACK_HELPED_USERS_1'); ?></p>
		</div>
		<?php
		}
		
		if (!RSTicketsProHelper::UserPostedFeedback($this->article->id)) {
		?>
		<div class="rst-kb-helpful-container">
			<span class="rst-kb-helpful-message"><?php echo JText::_('RST_KB_ARTICLE_FEEDBACK_HELPFUL_MAIN_TITLE'); ?></span>
			<a href="javascript:void(0);" class="rst-kb-helpful-btn" onclick="RSTicketsPro.sendKBFeedback(<?php echo $this->article->id; ?>);"><span class="rsticketsproicon-thumbs-up"></span></a>
		</div>
		<?php
		}
		?>
	</div>
<?php } ?>
</div>