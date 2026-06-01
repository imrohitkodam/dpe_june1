<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateHelperText extends KTemplateHelperAbstract
{
    /**
     * Highlight string
     *
     * @param  array  $config [keyword="string, string="string"]
     * @return string
     */
    public function highlight($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'keyword' => null,
            'string'  => null,
        ]);

        $keyword = $config->keyword;
        $string  = $config->string;

        if ($string && $keyword)
        {
            if (version_compare(JVERSION, '4.0', '<')) {
                $string = str_ireplace($keyword, "<span class=\"label label-warning\">{$keyword}</span>", $string);
            } else {
                $string = str_ireplace($keyword, "<mark data-markjs=\"true\">{$keyword}</mark>", $string);
            }
        }

        return $string;
    }
}