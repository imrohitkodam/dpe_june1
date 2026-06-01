<?php
/**
 * @version    SVN: <svn_id>
 * @package    JLike
 * @copyright  Copyright (C) 2005 - 2015. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
?>
<div class="container-fluid">
	<div class="row hidden-phone mt-10 mb-10">
		<div class=" col-md-6 pl-0">
			<div class="">
			<?php
			$input = $this->filterForm->getInput('subuserfilter','filter');
			$input = str_replace('filter[subuserfilter]', 'onlysubuser', $input);//replace name
			$input = str_replace('filter_subuserfilter', 'onlysubuser', $input);//replace id
			$input = str_replace('onchange="this.form.submit();"', '', $input);//remove onchange
			$input = str_replace('selected="selected"', '', $input);//remove selected option
			echo $input;
			?>
			</div>
		</div>
	</div>
	<div class="row">
		<table class="table table-striped new">
			<tbody>
			<?php
				foreach ($this->aclGroups as $group): ?>
				<tr>
					<td width="1%">
						<input class="user_groups" type="checkbox" name="user_groups[]" value="<?php echo $group->id ?>" id="group_<?php echo $group->id ?>">
					</td>
				<td>
					<label class="checkbox" for="group_<?php echo $group->id ?>">
						<?php echo $group->title ?></td>
					</label>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
