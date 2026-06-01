<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

$options = [];
$option = JHTML::_('select.option', '{{image.value}}', '{{image.text}}');
$option->{'ng-attrs'} = 'ng-selected="imagePickerCtlr.selectedImage()" ng-repeat="image in imagePickerCtlr.images" data-img-src="{{image.data_img_src}}"';
$options[] = $option;

$attrs = [
    'list.attr' => [
        'ng-model' => 'imagePickerCtlr.imagechooser_value',
    ],
];

echo '<div ng-controller="ImagePickerController as imagePickerCtlr">';
echo EHtmlSelect::imagePickerListControl(
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_IMAGES',
    'COM_AUTOTWEET_VIEW_ITEMEDITOR_IMAGES_DESC',
    $options,
    'imagechooser',
    $attrs,
    null,
    'imagechooser'
);

echo '</div>';
