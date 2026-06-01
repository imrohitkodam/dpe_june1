<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

JLoader::register('TagsHelperRoute', JPATH_BASE . '/components/com_tags/helpers/route.php');

$active = Factory::getApplication()->getMenu()->getActive();
$authorised = Factory::getUser()->getAuthorisedViewLevels();

?>
<?php if (!empty($displayData)) : ?>
	<ul class="tags inline list-unstyled d-flex">
		<?php
		$url = 'index.php?option=com_tjlms&view=courses';

		// Get itemid
		$ComtjlmsHelper = new ComtjlmsHelper;
		$itemid         = $ComtjlmsHelper->getitemid($url);

		foreach ($displayData as $i => $tag) : ?>
			<?php if (in_array($tag->access, $authorised)) :

				$tagParams  = new Registry($tag->params);
				$link_class = $tagParams->get('tag_link_class', 'label label-info');

				$htmltag = '<div class="' . $link_class . ' br-10 mr-5">' . $this->escape($tag->title) . '</div>';

				if (!empty($active) && $active->getParams()->get('filter_tag') != 'none')
				{
					$url = Route::_($url . '&filter_tag=' . $tag->tag_id . '&Itemid=' . $itemid);
					$htmltag = '<a ' . 'href="' . $url . '"' . 'class="' . $link_class . ' br-10 mr-5">' . $this->escape($tag->title) . '</a>';
				}

				?>
				<li class="tag-<?php echo $tag->tag_id; ?> tag-list<?php echo $i; ?>" itemprop="keywords">
					<?php echo $htmltag; ?>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
