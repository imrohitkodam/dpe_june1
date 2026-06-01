<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>

<?= helper('behavior.jquery')?>

<script>
    kQuery(document).ready(function($) {
        var Export = new Docman.Export({url: '<?=$export_url?>'});
        Export.bind('exportComplete', function(e, data) {
            if (data.exported) {
                var msg = <?= json_encode(translate("EXPORT_DOWNLOAD"))?>;
                setTimeout(function() {
                    window.location = "<?=JRoute::_('index.php?option=com_docman&view=documents&export=1', false)?>";
                }, 3000);
            } else {
                var msg = <?= json_encode(translate("EXPORT_EMPTY"))?>;
            }
            $('#docman-export-bar').parent().removeClass('active');
            $('#docman-export-message').fadeOut('slow', function() {
                $(this).html(msg).fadeIn('slow');
                $('#docman-export-bar').addClass('bg-success').parent().addClass('progress-success');
            });
        });
        Export.bind('exportUpdate', function(e, data) {
            $('#docman-export-bar').css('width', data.completed + '%');
        });
        $('#docman-export-button').one('click', function(event) {
            event.preventDefault();
            $(this).attr('disabled', 'disabled');
            Export.start();
        });
    });
</script>


<div id="docman-export" class="k-ui-namespace k-small-inline-modal-holder mfp-hide">
    <div class="k-inline-modal">
        <form>
            <h3 class="k-inline-modal__title">
                <?=translate('Export to CSV')?>
            </h3>
            <p class="docman_export_dialog__message" id="docman-export-message"><?= translate('EXPORT_INIT')?></p>
            <div class="k-export-dialog__progress">
                <? if (version_compare(JVERSION, '4', '>=')): ?>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped" style="width: 0%" id="docman-export-bar"></div>
                <? else: ?>
                <div class="progress progress-striped active">
                    <div class="bar" style="width: 0%" id="docman-export-bar"></div>
                <? endif ?>
                </div>
            </div>
            <div class="k-export-dialog__buttons">
                <button type="button" class="k-button k-button--primary" id="docman-export-button"><?= translate('Export')?></button>
            </div>
        </form>
    </div>
</div>
