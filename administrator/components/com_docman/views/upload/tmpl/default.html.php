<?
/**
 * @package     FILEman
 * @copyright   Copyright (C) 2012 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<?= helper('ui.load'); ?>
<?= helper('behavior.validator') ?>
<?= helper('behavior.icon_map'); ?>


<? // Setting up 'translations' to be used in JavaScript ?>
<?= helper('translator.script', array('strings' => array(
    'Please wait for the upload to finish first',
    'You are not permitted to create documents in this category',
    'You will lose all unsaved data. Are you sure?',
    'Continue editing this document: {document}'
))); ?>


<? // Loading JavaScript ?>
<ktml:script src="media://com_docman/js/admin/upload.default.js" />


<script>
    kQuery(function() {
        new Docman.BatchForm({
            <? if (isset($onBeforeInitialize)): ?>
            'onBeforeInitialize': <?= $onBeforeInitialize ?>,
            <? endif ?>

            <? if (isset($paths)): ?>
            'selected_files': <?= json_encode($paths) ?>,
            <? endif ?>

            <? if (isset($selected_category)): ?>
            'selected_category': <?= json_encode($selected_category) ?>,
            <? endif ?>

            <? if (isset($show_uploader)): ?>
            'show_uploader': <?= json_encode($show_uploader) ?>,
            <? endif ?>
        });

    });
</script>


<!-- Wrapper -->
<div class="k-wrapper k-js-wrapper">

    <!-- Overview -->
    <div class="k-content-wrapper">

        <!-- Content -->
        <div class="k-content k-js-content">

            <div class="k-toolbar k-js-toolbar">
                <button class="k-button k-button--success k-js-save">
                    <span class="k-icon-check" aria-hidden="true"></span>
                    <span class="k-button__text"><?= translate('Save') ?></span>
                </button>
                <button class="k-button k-button--default k-js-cancel">
                    <span class="k-icon-x" aria-hidden="true"></span>
                    <span class="k-button__text"><?= translate('Cancel') ?></span>

                </button>
            </div>

            <!-- Component wrapper -->
            <div class="k-component-wrapper">

                <!-- Component -->
                <div class="k-component k-js-component">

                    <!-- Container -->
                    <div class="k-container">

                        <div style="display: none" class="k-js-success-message k-alert k-alert--success">
                            <?= translate('Documents have been successfully created.') ?>
                            <span style="display: none" class="k-js-close-modal-container"><?= translate('Click {wrapper_start}here{wrapper_end} to close the uploader.', array(
                                'wrapper_start' => '<a class="k-js-close-modal" href="#">',
                                'wrapper_end' => '</a>'
                            )); ?></span>
                        </div>

                        <div class="k-container__full">

                            <?= helper('com:files.uploader.container', array(
                                'container' => 'docman-files',
                                'element' => '.docman-batch-uploader',
                                'options'   => array(
                                    'multi_selection' => true,
                                    'multipart_params' => [
                                        'folder' => 'tmp',
                                        'overwrite' => 1
                                    ],
                                    'autostart' => true,
                                    'url' => route('view=file&plupload=1&routed=1&format=json', false, false)
                                )
                            )); ?>

                        </div><!-- .k-container__full -->

                    </div><!-- .k-container -->

                    <!-- Container -->
                    <div class="k-container">

                        <div class="k-container__full">
                            <div class="k-heading"><?= translate('Select default values'); ?></div>
                        </div>

                    </div>

                    <!-- Container -->
                    <div class="k-container">

                        <form class="k-js-batch-form k-js-form-controller" id="document-batch">

                            <input type="hidden" id="automatic_humanized_titles" value="<?= $automatic_humanized_titles ?>" />

                            <!-- Main information -->
                            <div class="k-container__main" style="margin-bottom: 20px;">

                                    <div class="k-form-group">
                                        <label><?= translate('Category');?>:</label>
                                        <?= helper('listbox.categories', array(
                                            'name' => 'docman_category_id',
                                            'check_access' => true,
                                            'deselect' => false,
                                            'filter' => $category_filter,
                                            'attribs' => array('class' => 'required')
                                        ))?>
                                    </div>

                                    <? if(empty($hide_tag_field)) : ?>
                                        <div class="k-form-group">
                                            <label><?= translate('Tags'); ?></label>
                                            <?= helper('listbox.tags', array(
                                                'autocreate' => $can_create_tag
                                            )) ?>
                                        </div>
                                    <? endif; ?>

                            </div><!-- .k-container__main -->

                            <div class="k-container__sub">

                                <? if (empty($hide_owner_field)): ?>
                                    <div class="k-form-group">
                                        <label><?= translate('Owner');?>:</label>
                                        <?= helper('listbox.users', array(
                                            'name' => 'created_by',
                                            'deselect' => false,
                                            'selected' => @object('user')->getId(),
                                            'attribs' => array('class' => 'required')
                                        ))?>
                                    </div>
                                <? endif ?>

                            </div><!-- .k-container__sub -->

                        </form>

                    </div><!-- .k-container -->

                    <!-- Container -->
                    <div class="k-container">

                        <!-- Full width container -->
                        <div class="k-container__full">

                            <div class="k-heading"><?= translate('Preview'); ?></div>

                            <div class="k-js-form-container k-form-row-group" id="document_list">
                                <div class="k-form-row k-js-upload-warning">
                                    <div class="k-form-row__item k-form-row__item--label">
                                        <?= translate('Upload some files first') ?>
                                    </div>
                                </div>
                            </div>

                        </div><!-- .k-container__full -->

                    </div><!-- .k-container -->

                </div><!-- .k-component -->

            </div><!-- .k-component-wrapper -->

        </div><!-- .k-content -->

    </div><!-- .k-content-wrapper -->

</div><!-- .k-wrapper -->

<div class="k-dynamic-content-holder">
    <script data-inline type="text/html" class="k-js-document-form-template">
        <form action="<?= route('view=document&format=json') ?>" class="k-form-row k-js-document-form" method="post">

            <input type="hidden" name="storage_path" value="{{=it.storage_path}}" />
            <input type="hidden" name="automatic_thumbnail" value="1" />
            <input type="hidden" name="parameters[icon]" value="{{=it.icon}}" />

            <div class="k-form-row__item k-form-row__item--button">
                <button class="k-button k-button--default k-button--small k-js-remove-file" title="<?= translate('Remove this file from the list');?>">
                    <span class="k-icon-minus k-icon--error" aria-hidden="true"></span>
                    <span class="k-visually-hidden"><?= translate('Remove'); ?></span>
                </button>
            </div>
            <div class="k-form-row__item k-form-row__item--label">
                <label><?= translate('File name');?>:</label>
            </div>
            <div class="k-form-row__item k-form-row__item--input">
                <input class="k-form-control k-is-disabled k-js-filename" type="text" value="{{=it.filename}}" disabled/>
            </div>
            <div class="k-form-row__item k-form-row__item--label">
                <label><?= translate('Title');?>:</label>
            </div>
            <div class="k-form-row__item k-form-row__item--input">
                <input class="k-form-control k-js-title" type="text" name="title" value="{{=it.title}}" />
            </div>
        </form>
    </script>
</div>
