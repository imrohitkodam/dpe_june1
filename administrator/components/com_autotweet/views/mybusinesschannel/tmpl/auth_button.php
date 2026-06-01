<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

?>
<p id="authorizeGroup" class="text-center">
	<a id="authorizeButton" href="#" onclick="document.location='<?php

    echo $authUrl;

    ?>';" class="btn btn-info <?php

    echo $authUrlButtonStyle;

    ?>"><?php

    echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTHBUTTON_TITLE');

    ?></a>
</p>
<p>
<?php

    echo $message;

?>
</p>
<p><?php echo JText::_('COM_AUTOTWEET_CHANNEL_MYBUSINESS_AFTER_CLICK'); ?></p>
