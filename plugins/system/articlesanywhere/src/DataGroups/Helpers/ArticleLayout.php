<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class ArticleLayout
{
    public static function render($id, $attributes)
    {
        if ( ! $id)
        {
            return '';
        }

        if ( ! defined('JPATH_COMPONENT'))
        {
            define('JPATH_COMPONENT', JPATH_BASE . '/components/com_content');
        }

        if ( ! defined('JPATH_COMPONENT_SITE'))
        {
            define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_content');
        }

        if ( ! defined('JPATH_COMPONENT_ADMINISTRATOR'))
        {
            define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_content');
        }

        $params = Params::get();

        if (isset($attributes->force_content_triggers))
        {
            $params->force_content_triggers = $attributes->force_content_triggers;
            unset($attributes->force_content_triggers);
        }

        [$template, $layout] = self::getTemplateAndLayout($attributes);

        $view = new ArticleView;

        $view->setParams($id, $template, $layout, $params, $attributes);

        return $view->display();
    }

    private static function getTemplateAndLayout($data)
    {
        if ( ! isset($data->template) && isset($data->layout) && str_contains($data->layout, ':'))
        {
            [$data->template, $data->layout] = explode(':', $data->layout);
        }

        //    $article_layout = $this->item->get('article_layout');
        $article_layout = 'default';

        $layout = ! empty($data->layout)
            ? $data->layout
            : (($article_layout ?? null) ?: 'default');

        $template = ! empty($data->template)
            ? $data->template
            : JFactory::getApplication()->getTemplate();

        if (str_contains($layout, ':'))
        {
            [$template, $layout] = RL_Array::toArray($layout, ':');
        }

        // Layout is a template, so return default layout
        if (empty($data->template) && file_exists(JPATH_THEMES . '/' . $layout))
        {
            return [$layout, 'default'];
        }

        // Value is not a template, so a layout
        return [$template, $layout];
    }
}
