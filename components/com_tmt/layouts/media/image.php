<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
JHtml::_('behavior.modal', 'a.tjmodal');
jimport('joomla.filesystem.file');

$media = htmlspecialchars($displayData['media']);
$mediaUploadPath = $displayData['mediaUploadPath'];

$smallMediaImg = Uri::root() . $mediaUploadPath . '/' . $media;

if (File::exists(JPATH_SITE . '/' . $mediaUploadPath . '/' . 'S_' . $media))
{
	$smallMediaImg = Uri::root() . $mediaUploadPath . '/' . 'S_' . $media;
}

?>
<div class="media_image">
	<a href="<?php echo Uri::root() . $mediaUploadPath . '/' . $media; ?>"
		class="tjmodal"
		rel="{handler:'image'}"
		data-js-role='tjmodal'
		data-js-link="<?php echo Uri::root() . $mediaUploadPath . '/' . $media; ?>">
		<img src="<?php echo $smallMediaImg; ?>">
	</a>
</div>
