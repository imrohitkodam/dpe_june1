<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
jimport( 'joomla.application.module.helper' );
$document =	Factory::getDocument();

$input = Factory::getApplication()->input;

$course_cat = $input->get('catid', '', 'INT');

$main_category = $this->courses['main'];
unset($this->courses['main']);
$sub_cats = $this->courses;
?>

<script type="text/javascript">
	jQuery(document).ready(function() {
jQuery(".toggle-title").click(function(){
jQuery(this).siblings( ".toggle-content" ).toggle();
});
});
</script>
<div class="tjlms-inner">
<div class="row-fluid layout">


	<!--show courses-->

	<div class="span3 cat-layout">
		<div class="main-cat">

			<div class="tjlms-st1"><?php echo $main_category->title;?></div>
			<div class="tjlms-st2"><?php echo $main_category->description;?></div>

	</div>
	</div>
	<div class="span9 tjlms-courses-cat">
		<?php foreach($sub_cats as $ind=>$cat){ ?>
			<div class="tjlms-toggle-box">
				<ul class="unstyled com_tjlms_list">
					<li><div class="tjlms-toggle-title"><i class="icon-book" style="vertical-align:inherit;"></i>
							<strong style="vertical-align:text-bottom;"><?php echo $cat->title; ?></strong>
							<span class="tjlms-submenu-indicator">+</span>
<!--
							<i class="icon-caret-down pull-right"></i>
-->
						</div>
	<div class="tjlms-toggle-content">
				<?php
					$courses = $cat->courses;
					$path = $this->tjlmsFrontendHelper->getViewpath('com_tjlms', 'category','default_list');
					ob_start();
					include($path);
					$html = ob_get_contents();
					ob_end_clean();
					echo $html;
				?>

				</div>

					</li>
				</ul>

			</div>
		<?php } ?>
	</div>
</div>
</div>
