<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<? // Loading necessary Markup, CSS and JS ?>
<?= helper('ui.load') ?>


<!-- Wrapper -->
<div class="k-wrapper k-js-wrapper">

    <!-- Content wrapper -->
    <div class="k-content-wrapper">

        <!-- Content -->
        <div class="k-content k-js-content">

            <!-- Component wrapper -->
            <div class="k-component-wrapper">

                <!-- Component -->
                <form class="k-component k-js-component">

                    <!-- Container -->
                    <div class="k-container">

                        <!-- Main fields -->
                        <div class="k-container__main">

                            <div class="k-form-group">
                                <label for="logman_linker"><?= translate('Select a resource to link') ?></label>
                                <?= helper('listbox.linker') ?>
                                <p class="k-form-info"><?= translate('Non-selectable listed items are not frontend linkable') ?></p>
                            </div>

                        </div><!-- .k-container__main -->

                    </div><!-- .k-container -->

                </form><!-- .k-component -->

            </div><!-- .k-component-wrapper -->

        </div><!-- .k-content -->

    </div><!-- .k-content-wrapper -->

</div><!-- .k-wrapper -->


<script>
    kQuery(function($)
    {
        let linker = $('#logman_linker');

        linker.on('select2:select', function(e)
        {
            let select = $(this)
            let link = '<a class="logman_linker" href="' + select.val() + '">%s</a>';

            if (window.parent.jInsertEditorText)
            {
                window.parent.jInsertEditorText(link.replace(/%s/g, select.children().attr('title')), <?= json_encode($editor) ?>);
            }
            else if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances)
            {
                let editor = window.parent.Joomla.editors.instances[<?= json_encode($editor) ?>];

                if (editor.isCkeditor)
                {
                    let selected = editor.getEditor().getSelectedHtml().getHtml();
                    let content;

                    if ($(selected).is('img')) {
                        content = selected;
                    } else if ($(selected).is('a')) {
                        content = $(selected).html();
                    } else if (selected) {
                        content = selected;
                    } else {
                        content = select.children().attr('title');
                    }

                    link = link.replace(/%s/g, content);
                }
                else link = link.replace(/%s/g, select.children().attr('title'));

                editor.replaceSelection(link);
            }

            if (window.parent.Joomla.Modal)
            {
                var modal = window.parent.Joomla.Modal.getCurrent();
                if (modal) modal.close();
            }
            else if (window.parent.SqueezeBox) window.parent.SqueezeBox.close();

            if (typeof window.parent.kQuery !== 'undefined' && typeof window.parent.kQuery.magnificPopup !== 'undefined' && window.parent.kQuery.magnificPopup.instance) {
                window.parent.kQuery.magnificPopup.close();
            }
        });
    });
</script>
