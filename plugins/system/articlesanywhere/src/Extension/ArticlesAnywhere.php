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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Extension;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\Plugin\System as RL_SystemPlugin;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Replace;

defined('_JEXEC') or die;

final class ArticlesAnywhere extends RL_SystemPlugin
{
    public    $_lang_prefix = 'AA';
    protected $_jversion    = 4;
    protected $_page_types  = ['html', 'feed', 'pdf', 'xml', 'ajax', 'raw'];

    public function processArticle(
        string &$string,
        string $area = 'article',
        string $context = '',
        mixed  $article = null,
        int    $page = 0
    ): void
    {
        if ($context == 'com_finder.indexer')
        {
            return;
        }

        CurrentArticle::set($article);

        if ( ! RL_String::contains($string, Params::getTags(true)))
        {
            return;
        }

        $string = Replace::render($string, $area, $context, $article);
    }

    /**
     * @param string $buffer
     *
     * @return  bool
     */
    protected function changeDocumentBuffer(string &$buffer): bool
    {
        // Reset current article back from url
        CurrentArticle::setArticleByUrl();

        $buffer = Replace::render($buffer, 'component');

        return true;
    }

    /**
     * @param string $html
     *
     * @return  bool
     */
    protected function changeFinalHtmlOutput(string &$html): bool
    {
        if ( ! RL_String::contains($html, Params::getTags(true)))
        {
            return true;
        }

        if (RL_Document::isFeed())
        {
            $html = Replace::render($html);

            return true;
        }

        $params = Params::get();

        // only do stuff in body
        [$pre, $body, $post] = RL_Html::getBody($html);

        if ($params->handle_html_head)
        {
            $pre = Replace::render($pre, 'head');
        }

        $body = Replace::render($body, 'body');
        $html = $pre . $body . $post;

        return true;
    }


    /**
     * Adds the Articles Anywhere pagination page_param to the url params to ignore in caching
     */
    protected function handleOnAfterInitialise(): void
    {
        $app                      = JFactory::getApplication();
        $app->registeredurlparams = $app->registeredurlparams ?? (object) [];

        $params = Params::get();

        $app->registeredurlparams->{$params->page_param} = 'UINT';

        if (empty($params->registeredurlparams))
        {
            return;
        }

        foreach ($params->registeredurlparams as $param)
        {
            $app->registeredurlparams->{$param->name} = $param->type;
        }
    }

    /**
     * @param object $module
     * @param array  $params
     */
    protected function handleOnAfterRenderModule(object &$module, array &$params): void
    {
        if ( ! isset($module->content))
        {
            return;
        }

        // Reset current article back from url
        CurrentArticle::setArticleByUrl();

        Replace::render($module->content, 'module');
    }
}
