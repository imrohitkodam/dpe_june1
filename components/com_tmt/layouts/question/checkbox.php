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
use Joomla\CMS\Layout\FileLayout;

use Joomla\CMS\Language\Text;

$q        = $displayData['question'];
$item     = $displayData['item'];
$params   = $displayData['params'];
$mediaLib = $displayData['mediaLib'];
?>

<?php
$j = 1;

foreach ($q->answers as $a)
{
	$a = (object) $a;
	$checked = '';

	if (!empty($q->userAnswer))
	{
		if (in_array($a->id, $q->userAnswer, true))
		{
			$checked = 'checked';
		}
	}
	?>

	<div class="col-md-6 col-xs-12">
		<label class="checkbox font-normal input-label">
			<input type="checkbox"
				onclick="tjlms.test.checkcount('<?php echo $q->correct_answer; ?>','<?php echo $q->question_id;?>', '<?php echo $a->id;?>', '<?php echo $item->gradingtype; ?>')"
				name="questions[mcqs][<?php echo $q->question_id;?>][]"
				id="questions<?php echo $q->question_id;?>"
				value="<?php echo $a->id;?>" <?php echo $checked;?> />
			<?php  echo htmlentities(Text::_(trim($a->answer)));?>
			<span class="checkmark"></span>
		</label>

		<?php
		// Use layouts to render media elements
		if (!empty($a->media_id))
		{
			$original_media_type = $a->media_type;

			if (strpos($a->media_type, 'video') !== false)
			{
				$a->media_type = 'video';
			}
			elseif(strpos($a->media_type, 'image') !== false)
			{
				$a->media_type = 'image';
			}
			elseif(strpos($a->media_type, 'audio') !== false)
			{
				$a->media_type = 'audio';
			}
			else
			{
				$a->media_type = 'file';
			}

			$layout = new FileLayout($a->media_type, JPATH_ROOT . '/components/com_tmt/layouts/media');
			$mediaData   = array();
			$mediaData['media'] = $a->source;
			$mediaData['mediaUploadPath'] = $mediaLib->mediaUploadPath;
			$mediaData['originalMediaType'] = $original_media_type;
			$mediaData['originalFilename'] = $a->original_filename;
			$mediaData['media_type'] = $a->media_type;

			echo $layout->render($mediaData);
		}
		?>
	</div>

	<?php
	$j++;
}
