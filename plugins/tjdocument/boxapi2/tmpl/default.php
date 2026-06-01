<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
?>
<?php if ($error) {?>
	<div class="alert alert-error">
		<?php echo $error;?>
	</div>
<?php }else{
	?>
	<div class="preview-container" style="height:400px; width:100%;"></div>
<?php } ?>
<script>
tjBoxapi2.initializeViewer('<?php echo $document_id;?>', '<?php echo $access_token;?>', <?php echo json_encode($data);?>);
</script>
