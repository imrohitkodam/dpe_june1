<?php
/**
* @package Joomla Module for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
* @author Rimjhim
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
<?php
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<form id="login-register" method="post" action="index.php?option=plg_bbpass&action=loginregister&currentUrl=<?php echo base64_encode(Uri::getInstance()->toString());?>&return=<?php echo htmlspecialchars($return, ENT_QUOTES, 'UTF-8'); ?>">

            <input type="email" placeholder="your@email.com" name="email" autofocus class="input-medium"/>
            <p><?php echo JText::_("MOD_BBPASS_LOGIN_LINK_HELP");?></p>

            <button type="submit" class="btn btn-primary"><?php echo JText::_("MOD_BBPASS_LOGIN_REGISTER");?></button>
		
		   <?php echo JHtml::_('form.token'); ?>
 </form>
