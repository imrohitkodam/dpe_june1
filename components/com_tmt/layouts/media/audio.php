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
use Joomla\CMS\Language\Text;

$media = htmlspecialchars($displayData['media']);
$mediaUploadPath = $displayData['mediaUploadPath'];
$mediaType = str_replace('.', '/', $displayData['originalMediaType']);
?>
<div class="media_audio">
	<audio controls>
	  <source src="<?php echo JURI::root() . $mediaUploadPath . '/' . $media; ?>">
	  <?php echo Text::_('COM_TMT_QUESTION_MEDIA_AUDIO_NOT_SUPPORTED_BY_BROWSER'); ?>
	</audio>
</div>
