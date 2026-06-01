<?php
/**
 * @package       TJLMS
 * @subpackage  com_tjlms
 *
 * @author       Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Helper\ModuleHelper;

if (!empty($this->tagDetails) && count($this->tagDetails) == 1)
{
	$tagData = current($this->tagDetails);
	?>
		<h1><?php echo $tagData->title; ?></h1>
		<p><?php echo $tagData->description; ?></p>
	<?php
}


	$modules = ModuleHelper::getModules('tj-tags');

	?>
	<div class="container-fluid">
	<?php
		foreach ($modules as $module)
		{
			echo ModuleHelper::renderModule($module);
		}
?>
	</div>
