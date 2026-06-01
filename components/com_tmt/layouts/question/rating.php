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
use Joomla\Registry\Registry;

$q = $displayData['question'];
$registry = new Registry;
$registry->loadString($q->params);
$testParams = $registry;

if ($testParams->get('rating_label'))
{
$ratinglabels = explode(',', $testParams->get('rating_label'));
}
?>

<?php
$a       = $q->answers['0']['answer'];
$limit   = $q->answers['1']['answer'];

$checked = '';

if (isset($q->userAnswer))
{
	$checked = 'checked';
}
?>

<div class="clearfix table-responsive pb-10">
	<table class="table">
		<thead>
			<tr>
				<?php
				if (!empty($ratinglabels))
				{
					foreach ($ratinglabels as $key => $value)
					{
						?>
						<th class="center">
							<?php echo $value; ?>
						</th>
						<?php
					}
				}
				else
				{
					for ($j = (int) $a; $j <= (int) $limit; $j++)
					{
						?>
						<th class="center">
							<?php echo $j; ?>
						</th>
						<?php
					}
				}
				?>
			</tr>
		</thead>

		<tbody>
			<tr>
				<?php
				for ($k = (int) $a; $k <= (int) $limit; $k++)
				{
					?>

					<td class="center">
						<label class="input-label">
							<input type="radio"
								name="questions[rating][<?php echo $q->question_id;?>]"
								id="questions<?php echo $q->question_id;?>"
								value="<?php echo $k;?>"
								<?php
								if ($k == $q->userAnswer)
								{
									echo $checked;
								}
								?>
							/>
							<span class="radiobtn"></span>
						</label>
					</td>

					<?php
				}
				?>
			</tr>
		</tbody>
	</table>
</div>
