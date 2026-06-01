<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('stylesheet', 'com_rsticketspro/awesomplete.css', array('relative' => true, 'version' => 'auto'));
JHtml::_('script', 'com_rsticketspro/awesomplete.min.js', array('relative' => true, 'version' => 'auto'));
JHtml::_('script', 'com_rsticketspro/dashboard.js', array('relative' => true, 'version' => 'auto'));

if ($this->params->get('show_page_heading', 1))
{
	?>
	<h1 class="rst-heading"><?php echo $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
	<?php
}

if ($this->globalMessage && $this->globalMessagePosition === 'top')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}
?>
<form method="post" action="<?php echo $this->search_link; ?>">
	<?php if ($this->params->get('show_search', 1)) { ?>
	<div class="rst-dashboard-search rst-dashboard-section text-center">
		<div class="btn-group">
			<input type="text" placeholder="<?php echo $this->escape(JText::_('RST_SEARCH_HELPDESK')); ?>" class="form-control input-xlarge" name="search" autocomplete="off" id="rsticketspro_searchinp" />
			<button type="submit" class="btn btn-primary"><i id="rstickets_search_icon" class="rsticketsproicon-search"></i><?php echo JHtml::_('image', 'com_rsticketspro/loading.gif', '', array('id' => 'rsticketspro_loading', 'style' => 'display:none;'), true); ?></button>
		</div>
	</div>
	<?php } ?>

	<div class="rst-dashboard-items rst-dashboard-section <?php echo RsticketsproAdapterGrid::row(); ?>">
		<div class="<?php echo RsticketsproAdapterGrid::column(4); ?>">
			<div class="rst-dashboard-item <?php echo RsticketsproAdapterCard::render(); ?> bg-white">
				<div class="<?php echo RsticketsproAdapterCard::render('body'); ?> text-center">
					<?php if ($this->db_thumb_type == 'icons') { ?>
					<h2><i class="rsticketsproicon-mail"></i> <a class="rst-title" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=submit'); ?>"><?php echo JText::_('RST_SUBMIT_TICKET'); ?></a></h2>
					<?php } else { ?>
					<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=submit'); ?>">
						<?php
						if ($this->db_submit_ticket_thumb) {
							$submit_ticket_thumb = JHtml::_('image', $this->db_submit_ticket_thumb, JText::_('RST_SUBMIT_TICKET'), array(), false);
						} else {
							$submit_ticket_thumb = JHtml::_('image', 'com_rsticketspro/kb-icon.png', JText::_('RST_SUBMIT_TICKET'), array(), true);
						}
						
						echo $submit_ticket_thumb;
						?>
					</a>
					<h2 class="rst-title"><?php echo JText::_('RST_SUBMIT_TICKET'); ?></h2>
					<?php } ?>
					<div class="rst-caption">
						<p><?php echo JText::_($this->params->get('submit_ticket_desc')); ?></p>
						<a class="btn btn-secondary" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=submit'); ?>"><?php echo JText::_('RST_DB_SUBMIT'); ?></a>
					</div>
				</div>
			</div>
		</div>
		<div class="<?php echo RsticketsproAdapterGrid::column(4); ?>">
			<div class="rst-dashboard-item <?php echo RsticketsproAdapterCard::render(); ?> bg-white">
				<div class="<?php echo RsticketsproAdapterCard::render('body'); ?> text-center">
					<?php if ($this->db_thumb_type == 'icons') { ?>
					<h2><i class="rsticketsproicon-clipboard"></i> <a class="rst-title" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets'); ?>"><?php echo JText::_('RST_VIEW_TICKETS'); ?></a></h2>
					<?php } else { ?>
					<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets'); ?>">
						<?php
						if ($this->db_view_tickets_thumb) {
							$view_tickets_thumb = JHtml::_('image', $this->db_view_tickets_thumb, JText::_('RST_VIEW_TICKETS'), array(), false);
						} else {
							$view_tickets_thumb = JHtml::_('image', 'com_rsticketspro/kb-icon.png', JText::_('RST_VIEW_TICKETS'), array(), true);
						}
						
						echo $view_tickets_thumb;
						?>
					</a>
					<h2 class="rst-title"><?php echo JText::_('RST_VIEW_TICKETS'); ?></h2>
					<?php } ?>
					<div class="rst-caption">
						<p><?php echo JText::_($this->params->get('view_tickets_desc')); ?></p>
						<a class="btn btn-secondary" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets'); ?>"><?php echo JText::_('RST_DB_VIEW'); ?></a>
					</div>
				</div>
			</div>
		</div>
		<div class="<?php echo RsticketsproAdapterGrid::column(4); ?>">
			<div class="rst-dashboard-item <?php echo RsticketsproAdapterCard::render(); ?> bg-white">
				<div class="<?php echo RsticketsproAdapterCard::render('body'); ?> text-center">
					<?php if ($this->db_thumb_type == 'icons') { ?>
					<h2><i class="rsticketsproicon-search"></i> <a class="rst-title" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=search'); ?>"><?php echo JText::_('RST_SEARCH_TICKETS'); ?></a></h2>
					<?php } else { ?>
					<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets'); ?>">
						<?php
						if ($this->db_search_tickets_thumb) {
							$search_tickets_thumb = JHtml::_('image', $this->db_search_tickets_thumb, JText::_('RST_SEARCH_TICKETS'), array(), false);
						} else {
							$search_tickets_thumb = JHtml::_('image', 'com_rsticketspro/kb-icon.png', JText::_('RST_SEARCH_TICKETS'), array(), true);
						}
						
						echo $search_tickets_thumb;
						?>
					</a>
					<h2 class="rst-title"><?php echo JText::_('RST_SEARCH_TICKETS'); ?></h2>
					<?php } ?>
					<div class="rst-caption">
						<p><?php echo JText::_($this->params->get('search_tickets_desc')); ?></p>
						<a class="btn btn-secondary" href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=search'); ?>"><?php echo JText::_('RST_DB_SEARCH'); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	if ($this->params->get('show_tickets', 1))
	{
		?>
		<div id="rsticketspro_dashboard_tickets" class="rst-dashboard-tickets rst-dashboard-section <?php echo RsticketsproAdapterGrid::row(); ?>">
			<div class="<?php echo RsticketsproAdapterGrid::column(12); ?>">
				<h2 class="rst-section-title"><?php echo JText::_('RST_MY_TICKETS'); ?></h2>
				<?php
				if ($this->user->get('guest'))
				{
					?>
					<div class="rst-dashboard-login">
						<p><?php echo JText::_('RST_YOU_HAVE_TO_BE_LOGGED_IN'); ?></p>
						<a class="btn btn-primary" href="<?php echo $this->login_link; ?>"><i class="icon-lock"></i> <?php echo JText::_('RST_CLICK_HERE_TO_LOGIN'); ?></a>
					</div>
					<?php
				}
				else
				{
					if (count($this->tickets))
					{
						?>
						<table class="table table-striped table-hover table-bordered">
							<thead>
							<tr>
								<th><?php echo JText::_('RST_TICKET_SUBJECT'); ?></th>
								<th><?php echo JText::_('RST_TICKET_STATUS'); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($this->tickets as $ticket)
							{
								$hasReply = isset($ticket->message);
								?>
								<tr class="rst-dashboard-status-<?php echo strtolower(str_replace(array(' ', '_'), '-', preg_replace('/[^a-zA-Z0-9\s-]/', '', $ticket->status_name))); ?>">
									<td><?php if ($hasReply) { ?><strong><?php } ?><a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=ticket&id='.$ticket->id.':'.JFilterOutput::stringURLSafe($ticket->subject)); ?>"><?php echo $this->escape($ticket->subject); ?></a><?php if ($hasReply) { ?> (1)</strong><?php } ?></td>
									<td><?php echo $this->escape(JText::_($ticket->status_name)); ?></td>
								</tr>
								<?php
							}
							?>
							</tbody>
						</table>
						<?php
					}
					else
					{
						?>
						<div class="alert alert-info">
							<p><?php echo JText::_('RST_NO_RECENT_ACTIVITY'); ?></p>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>
		<?php
	}
	
	if ($this->params->get('show_kb', 1)) {
		?>
		<div id="rsticketspro_dashboard_knowledgebase" class="rst-dashboard-section">
			<h2 class="rst-section-title"><?php echo JText::_('RST_KNOWLEDGEBASE'); ?></h2>
			<?php
			if (count($this->categories)) {
				if ($this->params->get('split_kb_to_tabs', 0)) {
					$kb_tabs	= new RsticketsproAdapterNavpills('rst-dashboard-kb-tabs');
					$top_cats	= array();
					
					foreach ($this->categories as $i => $cat) {
						$top_cats[]	= $this->model->getCategories($cat->id);
						$kb_tabs->addTitle($cat->name, 'rst_kb_' . preg_replace('/[^a-zA-Z0-9]+/', '', strtolower($cat->name)));
					}
					
					if (count($top_cats)) {
						foreach ($top_cats as $top_cat) {
							$parts		= array_chunk($top_cat, 3);
							$kb_rows	= '';
							
							if (count($parts)) {
								foreach ($parts as $part) {
									$kb_rows .= '<div class="rst-dashboard-kb-row ' . RsticketsproAdapterGrid::row() . '">';
								
									foreach ($part as $category) {
										$this->category = $category;
										$kb_rows .= $this->loadTemplate('card');
									}
									$kb_rows .= '</div>';
								}
								$kb_tabs->addContent($kb_rows);
							} else {
								$kb_tabs->addContent('<div class="alert alert-info"><span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only">' . JText::_('INFO') . '</span> ' . JText::_('RST_NO_KB_SUBCATEGORIES') . '</div>');
							}
						}
						
						$kb_tabs->render();
					} else {
					?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span> <?php echo JText::_('RST_NO_KB_CATEGORIES'); ?>
					</div>
					<?php
					}
				} else {
					$parts = array_chunk($this->categories, 3);
					foreach ($parts as $part) {
						?>
						<div class="rst-dashboard-kb-row <?php echo RsticketsproAdapterGrid::row(); ?>">
						<?php	
						foreach ($part as $category) {
							$this->category = $category;
							echo $this->loadTemplate('card');
						}
						?>
						</div>
			<?php
					}
				}
			} else {
			?>
			<div class="alert alert-info">
				<span class="fa fa-info-circle" aria-hidden="true"></span> <?php echo JText::_('RST_NO_KB_CATEGORIES'); ?>
			</div>
			<?php } ?>
		</div>
		<?php
	}
	?>
</form>

<?php
if ($this->globalMessage && $this->globalMessagePosition === 'bottom')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}
?>

<input type="hidden" name="kb_itemid" value="<?php echo $this->kb_itemid; ?>" />
<input type="hidden" name="curr_itemid" value="<?php echo $this->itemid; ?>" />