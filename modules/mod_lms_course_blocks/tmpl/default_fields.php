<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_course_progress
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

/* Course fields */
$count = count($modData->fieldsData);
?>

<div class="courseInfo panel panel-default br-0">
	<div class="panel-heading">
		<span class="panel-heading__title">
			<i class="fa fa-info-circle"></i>
			<?php // Added in version Shika 1.3.2 version. ?>
			<span class="course_block_title"><?php echo Text::_('MOD_LMS_COURSE_FIELDS')?></span>
		</span>
	</div>
	<div class="panel-body p-15">
		<div class="container-fluid">
			<div class="row">
				<div class="col-xs-12">
				<?php if(!empty($modData->fieldsData))
					{
						foreach($modData->fieldsData as $fieldData)
						{
							if(!empty($fieldData) && $count !== 1)
							{
								echo $fieldData;?>
								<hr>
						<?php }
							else
							{
								echo $fieldData;
							}

						}
					}
					else
					{
						// Added in version Shika 1.3.2.
						 echo Text::_('MOD_LMS_NO_COURSE_FIELDS');
					}
						?>
				</div>
			</div>

		</div>
	</div>
</div>
