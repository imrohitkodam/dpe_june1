<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
?>
<div class="dashboard-container">
	<?php foreach ($this->buttons as $button) { ?>
		<?php if ($button['access']) { ?>
			<div class="dashboard-info dashboard-button">
				<a href="<?php echo $button['link']; ?>">
					<?php echo JHtml::image($button['image'], $button['text'], null, true); ?>
					<span class="dashboard-title"><?php echo $button['text']; ?></span> 
				</a> 
			</div>
		<?php } ?>
	<?php } ?>
</div>
<div class="clearfix"></div>