<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content tjBs3 container-fluid">
	<form action="" method="post" name="adminForm" id="adminForm" class='form-validate'>

		<!-- Page heading for lessons pin view. -->
		<div class="row">
			<div class="col-xs-12">
				<h2><?php echo $this->pageTitle; ?></h2>
			</div>
		</div>

		<hr class="row visible-xs hr hr-condensed mt-0 mb-0" />
		<div class="row tjlms-filters">
			<div class="col-xs-12">
				<!-- Load the default joomla searchbox. -->
				<?php

				$searchData = array('view' => $this);
				$searchData['options']['filtersHidden'] = 1;

				if ($this->activeFilters)
				{
					$searchData['options']['filtersHidden'] = 0;
				}

				echo LayoutHelper::render('joomla.searchtools.default', $searchData);
				?>
			</div>
		</div>
		<!-- Showing the lesson pin view. -->
		<div class="row flex-row" id="tjlms_pin_container" >
		<?php

			$pinclass = $this->menuparams->get('xsmall_device_pin_class') . ' ' .
			$this->menuparams->get('small_device_pin_class') . ' ' .
			$this->menuparams->get('medium_device_pin_class') . ' ' .
			$this->menuparams->get('large_device_pin_class') . ' ';

			$layout = new JLayoutFile('lessonpin');

			if (!empty($this->items))
			{
				foreach ($this->items as $data)
				{
					$data                              = (array) $data;
					$data['pinclass']                  = $pinclass;
					$data['launch_lesson_full_screen'] = $this->launch_lesson_full_screen;
					echo $layout->render($data);
				}
			}
			else
			{
			?>
			<div class="alert alert-info">
				<?php echo Text::_('COM_TJLMS_NO_LIBRARY_LESSONS'); ?>
			</div>
		<?php
			}
		?>
	    </div>

		<!-- Adding pagination. -->
		<div class="pager">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	</form>
</div>

