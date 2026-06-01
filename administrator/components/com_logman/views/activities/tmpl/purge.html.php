<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>

<?= helper('behavior.jquery') ?>

<script>
kQuery(document).ready(function($) {
    var request = function(append_url) {
        var url = '?option=com_logman&view=activities';

        if (append_url) {
            url += append_url;
        }

        return $.ajax(url, {
            type: 'post',
            dataType: 'json',
            data: {
                '_action': 'purge',
                'csrf_token': <?= json_encode(object('user')->getSession()->getToken()) ?>
            },
            success: function(data, textStatus, jqXHR) {
                alert(<?= json_encode(translate('Successfully purged')) ?>);
                window.parent.location.reload();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert(<?= json_encode('An error occurred during request'); ?>);
            }
        });
    };

    $('#logman-purge-until').click(function(e) {
        e.preventDefault();
        request('&end_date='+$('#purge_date').val());
    });

    $('#logman-purge-all').click(function(e) {
        e.preventDefault();
        if (confirm(<?=json_encode(translate('This will delete all activities on your site. Are you sure?'))?>)) {
            request();
        }
    });
});
</script>


<div id="logman-purge" class="k-ui-namespace k-small-inline-modal-holder mfp-hide">
    <div class="k-inline-modal">
        <form>
            <h3 class="k-inline-modal__title">
                <?=translate('Purge Activities')?>
            </h3>

            <div class="k-form-group">
                <label for="purge_until"><?=translate('Purge activities before ')?></label>
                <?= helper('behavior.calendar',
                    array(
                        'attribs' => array('id' => 'purge_date', 'class' => 'k-form-control'),
                        'name'    => 'purge_date',
                        'value'   => $end_date,
                        'format'  => '%Y-%m-%d'
                    ));
                ?>
            </div>

            <div class="k-form-group">
                <button class="k-button k-button--primary logman-purge-dialog__purge" id="logman-purge-until"><?= translate('Purge')?></button>
                <span class="k-this-or-that"><?=translate('or')?></span>
                <button class="k-button k-button--mini k-button--danger logman-purge-dialog__purgeall" id="logman-purge-all"><?= translate('Purge all activities')?></button>
            </div>
        </form>
    </div>
</div>
