<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;


$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/vendor/jquery/js/jquery.min.js');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/dpe.css');
?>

<?php
if ($this->item)
{
?>
<div id="certificateContent">
<?php
	echo $this->item->generated_body;
?>
</div>
<?php
}
?>

<script type="text/javascript">
    
  jQuery(document).ready(function() {
    // Find every img tag with a src attribute inside the element with ID "certificateContent"
    jQuery('#certificateContent img[src]').each(function() { 
        // Get the current src attribute value
        var  src = jQuery(this).attr('src');
        // Add a forward slash after the src attribute value
        if (src) {
            jQuery(this).attr('src', '/' + src );
        }
    });
});


</script>
