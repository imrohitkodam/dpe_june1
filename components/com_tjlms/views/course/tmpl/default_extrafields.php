<?php
 /**
  * @version     1.5
  * @package     com_jticketing
  * @copyright   Copyright (C) 2014. All rights reserved.
  * @license     GNU General Public License version 2 or later; see LICENSE.txt
  * @author      Techjoomla <extensions@techjoomla.com> - http://techjoomla.com
  */
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

if(count($this->extraData))
{ ?>
	<div class="tjlms-course-additionaldata">
		<div class="row-fluid">
			<table class="table table-bordered tjlms_course_toc_listing no-margin no-padding unstyled_list tjlms-table">
				<caption><h5> <?php echo Text::_("COM_TJLMS_EXTRAFIELD_CAPTION");?></h5></caption><?php
				foreach($this->extraData as $f)
				{
					if ($f->value)
					{
						if ($f->type != 'user')
						{ ?>
							<tr>
								<td>
									<strong><?php echo $f->label;?></strong>
								</td>
								<td><?php
									if (!is_array($f->value))
									{
										if ($f->type == 'file')
										{
											echo '<img src="'. Uri::root() . $f->value .'" />';
										}
										else
										{
											if (strlen($f->value) > 40)
											{
												echo substr($f->value, 0, 40).'...';
											}
											else
											{

												echo $f->value;
											}
											//echo $f->value;
										}
									}
									else
									{
										foreach($f->value as $option)
										{
											//~ echo "<pre>";print_r($option);echo "</pre>";
											echo $option->options; ?>
											<br/><?php
										}
									}?>
								</td>
							</tr><?php
						}
					}
				} ?>
			</table>
		</div>
	</div><?php
}
