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
<div id="contentcreation" class="tab-pane fade">
<?php

echo '<a id="#texthandling"></a>';
require_once 'feed_texthandling.php';

echo '<a id="#links"></a>';
require_once 'feed_links.php';

echo '<a id="#images"></a>';
require_once 'feed_images.php';

echo '<a id="#enclosures"></a>';
require_once 'feed_enclosures.php';

echo '<a id="#languages"></a>';
require_once 'feed_languages.php';

?>
</div>
