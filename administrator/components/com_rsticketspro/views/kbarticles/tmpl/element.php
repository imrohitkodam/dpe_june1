<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
$listOrder 	= $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction')); ?>

<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=kbarticles&layout=element'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	if (empty($this->items))
	{
		?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('INFO'); ?></span>
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php
	}
	else
	{
		?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th width="20"><?php echo JHtml::_('grid.checkall'); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_KB_ARTICLE_NAME', 'a.name', $listDirn, $listOrder); ?></th>
					<th><?php echo JHtml::_('searchtools.sort', 'RST_KB_CATEGORY_NAME', 'c.name', $listDirn, $listOrder); ?></th>
					<th width="1%" class="text-center" align="center"><?php echo JHtml::_('searchtools.sort', 'RST_PRIVATE', 'a.private', $listDirn, $listOrder); ?></th>
					<th width="1%" class="text-center" align="center"><?php echo JHtml::_('searchtools.sort', 'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->items as $i => $item)
			{
				?>
				<tr>
					<td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
					<td><a onclick="window.parent.elSelectEvent('<?php echo $item->id; ?>', this.innerText);" href="javascript: void(0);"><?php echo $item->name != '' ? $this->escape($item->name) : JText::_('RST_NO_TITLE'); ?></a></td>
					<td>
						<?php
						if ($item->category_id)
						{
							echo $item->category_name;
						}
						else
						{
							echo JText::_('RST_KB_NO_PARENT');
						}
						?>
					</td>
					<td class="text-center" align="center"><?php echo $item->private ? JText::_('JYES') : JText::_('JNO'); ?></td>
					<td class="text-center" align="center"><?php echo JHtml::_('jgrid.published', $item->published, $i, 'kbarticles.'); ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		echo $this->pagination->getListFooter();
	}
	?>
	
	<?php echo JHtml::_( 'form.token' ); ?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="tmpl" value="component" />
</form>