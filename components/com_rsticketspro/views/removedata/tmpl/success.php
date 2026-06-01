<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2018 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

$this->app->enqueueMessage(JText::_('COM_RSTICKETSPRO_DATA_HAS_BEEN_SUCCESSFULLY_ANONYMISED'));
?>

<?php if ($this->params->get('show_page_heading', 1)) { ?>
	<h1 class="rst-heading"><?php echo $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
<?php }

if ($this->globalMessage && $this->globalMessagePosition !== 'disabled')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}

if ($this->show_footer)
{
	echo $this->footer;
}