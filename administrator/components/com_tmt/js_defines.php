<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * TMt is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

$tjlmsparams	= ComponentHelper::getParams('com_tjlms');
?>

<script>

	var nonvalid_extension = "<?php echo Text::_('COM_TMT_UPLOAD_EXTENSION_ERROR');?>"
	var lesson_upload_size = <?php echo $tjlmsparams->get('lesson_upload_size', 10, 'INT');?>;

</script>
