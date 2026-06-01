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
$mediaOriginalFileName = htmlspecialchars($displayData['originalFilename']);
?>
<div class="media_file">
	<label>
		<a href="<?php echo JURI::root() . $mediaUploadPath . '/' . $media; ?>" target="_blank">
		<?php echo $mediaOriginalFileName; ?>
		</a>
	</label>
</div>
