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

$media = htmlspecialchars($displayData['media']);
$mediaUploadPath = $displayData['mediaUploadPath'];
$videoType = $displayData['originalMediaType'];
?>
<div class="media_video">
	<?php
	if ($videoType == 'video.youtube')
	{
		?>
		<iframe width='300' height='200' src="<?php echo $media; ?>" frameborder="0" allowfullscreen=""></iframe>
		<?php
	}
	elseif ($videoType == 'video.vimeo')
	{
		?>
		<iframe src="<?php echo $media; ?>" width='400' height='200' frameborder="0" allowfullscreen=""></iframe>
		<?php
	}
	?>
</div>
