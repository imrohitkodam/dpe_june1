<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

$category	= $this->category;
$subcats	= $this->kb_subcats_limit >= 0 ? $this->model->getCategories($category->id) : array();

if ($category->thumb) {
	$thumb = JHtml::_('image', 'components/com_rsticketspro/assets/thumbs/small/'.$category->thumb, $category->name, array(), false);
} else {
	$thumb = JHtml::_('image', 'com_rsticketspro/kb-icon.png', $category->name, array(), true);
}
?>

<div class="<?php echo RsticketsproAdapterGrid::column(4); ?>">
	<div class="rst-dashboard-kb-item <?php echo RsticketsproAdapterCard::render(); ?> bg-white">
		<div class="<?php echo RsticketsproAdapterCard::render('body'); ?>">
			<h4 class="rst-title">
				<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase&cid='.RSTicketsProHelper::KbSEF($category), true, $this->kb_itemid); ?>">
				<?php echo $thumb . ($category->show_name ? $this->escape($category->name) : ''); ?>
				</a>
			</h4>
			<?php if ($category->description) { ?>
			<div class="rst-description"><?php echo $category->description; ?></div>
			<?php } ?>
			<?php if (count($subcats)) { ?>
			<ul class="rst-subcategories">
				<?php
				foreach ($subcats as $i => $subcat) {
					if (!$this->kb_subcats_limit || ($this->kb_subcats_limit > 0 && $i < $this->kb_subcats_limit)) {
				?>
				<li>
					<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase&cid='.RSTicketsProHelper::KbSEF($subcat), true, $this->kb_itemid); ?>"><?php echo $this->escape($subcat->name); ?></a>
				</li>
				<?php
					}
				}
				?>
			</ul>
			<?php } ?>
		</div>
	</div>
</div>