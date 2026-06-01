<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

?>
<form action="" id="adminForm" name="adminForm" method="post">
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="option" value="com_dpe"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
