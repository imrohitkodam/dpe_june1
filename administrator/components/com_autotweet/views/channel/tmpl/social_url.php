<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

    if (!isset($social_target)) {
        $social_target = 'social_url';
    }

?>
		<input type="hidden" value="<?php echo $this->item->xtform->get($social_target); ?>" id="<?php

            echo $social_target;

        ?>" name="xtform[<?php

            echo $social_target;

        ?>]">
		<div class="<?php

            echo $social_target;

        ?>"><?php

            $social_url_value = $this->item->xtform->get($social_target);

            if (!empty($social_url_value)) {
                echo AutotweetModelChanneltypes::formatUrl($this->item->channeltype_id, $social_url_value);
            }

        ?></div>
