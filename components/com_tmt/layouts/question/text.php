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

$q = $displayData['question'];
?>

<div class="col-sm-8">
	<input type="text"
	name="questions[subjective][<?php echo $q->question_id;?>]"
	id="questions<?php echo $q->question_id;?>"
	class="inputbox form-control"
	size="50"
	value="<?php echo htmlentities($q->userAnswer);?>"/>
</div>
