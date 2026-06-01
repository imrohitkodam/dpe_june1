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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * Utility class for creating HTML Grids.
 *
 * @since       3.0
 */
abstract class EHtmlGrid
{
    public const IMG_ICON_YES = '<i style="color: green;" class="xticon fas fa-check"></i>';

    public const IMG_ICON_NO = '<i style="color: red;" class="xticon far fa-circle"></i>';

    /**
     * Method to create a clickable icon to change the state of an item.
     *
     * @param mixed $value    Either the scalar value or an object (for backward compatibility, deprecated)
     * @param int   $i        The index
     * @param bool  $withLink Param
     *
     * @return string
     */
    public static function published($value, $i, $withLink = true)
    {
        return self::publishedWithIcons($value, $i, $withLink);
    }

    /**
     * Method to create a clickable icon to change the state of an item.
     *
     * @param mixed $value    Either the scalar value or an object (for backward compatibility, deprecated)
     * @param int   $i        The index
     * @param bool  $withLink Param
     *
     * @return string
     */
    public static function publishedWithIcons($value, $i, $withLink = false)
    {
        if (is_object($value)) {
            $value = $value->published;
        }

        $img = (bool) $value ? self::IMG_ICON_YES : self::IMG_ICON_NO;

        if (!$withLink) {
            return $img;
        }

        $task = (bool) $value ? 'unpublish' : 'publish';
        $action = (bool) $value ? JText::_('JLIB_HTML_UNPUBLISH_ITEM') : JText::_('JLIB_HTML_PUBLISH_ITEM');
        $href = '<a href="#" onclick="return Joomla.listItemTask(\'cb'.$i.'\',\''.$task.'\')" title="'.$action.'">'.$img.'</a>';

        return $href;
    }

    /**
     * Method to create a icon.
     *
     * @param mixed  $locked    Param
     * @param string $img1      Param
     * @param string $img0      Param
     * @param bool   $optimized Param
     *
     * @return string
     */
    public static function lockedWithIcons($locked, $img1 = '<i class="xticon fas fa-lock"></i>', $img0 = '<i class="xticon fas fa-lock-open"></i>', $optimized = true)
    {
        if (($optimized) && (!$locked)) {
            return null;
        }

        $img = (bool) $locked ? $img1 : $img0;
        // Return JHtml::_('image', $img, $alt, null, true);

        return $img;
    }

    /**
     * ajaxOrderingInit.
     *
     * @param string $option      Param
     * @param string $orderDir    Param
     * @param string $listTableId Param
     * @param string $formId      Param
     *
     * @return string
     */
    public static function ajaxOrderingInit($option, $orderDir, $listTableId = 'itemsList', $formId = 'adminForm')
    {
        $saveOrderingUrl = 'index.php?option='.$option.'&task=saveorder';
        JHtml::_('sortablelist.sortable', $listTableId, $formId, strtolower($orderDir), $saveOrderingUrl);
    }

    /**
     * ajaxOrderingColumn.
     *
     * @param bool $editstate Param
     * @param int  $ordering  Param
     *
     * @return string
     */
    public static function ajaxOrderingColumn($editstate, $ordering)
    {
        $output = [];
        $output[] = '<td class="order nowrap center hidden-phone">';

        if ($editstate) {
            $output[] = '<span class="sortable-handler iactive" >';
        } else {
            $disabledLabel = JText::_('JORDERINGDISABLED');
            $disableClassName = 'inactive tip-top';

            $output[] = '<span class="sortable-handler ';
            $output[] = $disableClassName;
            $output[] = '" title="';
            $output[] = $disabledLabel;
            $output[] = '" rel="tooltip">';
        }

        $output[] = '<i class="icon-menu"></i></span>';
        $output[] = '<input type="text" name="order[]" value="';
        $output[] = $ordering;
        $output[] = '" style="display:none;" />';
        $output[] = '</span>';
        $output[] = '</td>';

        return implode('', $output);
    }

    /**
     * basicOrderingColumn.
     *
     * @param string &$pagination Param
     * @param string $i           Param
     * @param string $count       Param
     * @param string $ordering    Param
     *
     * @return string
     */
    public static function basicOrderingColumn(&$pagination, $i, $count, $ordering)
    {
        $j3 = (EXTLY_J3);

        $output = [];
        $output[] = '<td class="order xt-col-span-2" align="center">';
        $output[] = '<span>';

        $orderup = $pagination->orderUpIcon($i, true, 'orderup', 'Move Up', $ordering);

        if (($j3) && ('&#160;' === $orderup)) {
            $output[] = '<span><a class="disabled btn btn-micro"  href="#"><i class="icon-empty"></i></a></span>';
        } else {
            $output[] = $orderup;
        }

        $output[] = '</span>';
        $output[] = '<span>';

        $orderdown = $pagination->orderDownIcon($i, $count, true, 'orderdown', 'Move Down', $ordering);

        if (($j3) && ('&#160;' === $orderdown)) {
            $output[] = '<span><a class="disabled btn btn-micro"  href="#"><i class="icon-empty"></i></a></span>';
        } else {
            $output[] = $orderdown;
        }

        $output[] = '</span>';

        $disabled = (null !== $ordering) ? '' : 'disabled="disabled"';
        $output[] = ' <input type="text" name="order[]" size="5" value="';
        $output[] = $ordering;
        $output[] = '" '.$disabled;
        $output[] = ' class="text_area input-ordering" style="text-align: center; width:auto;" />';
        $output[] = '</td>';

        return implode('', $output);
    }
}
