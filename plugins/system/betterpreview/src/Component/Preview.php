<?php
/**
 * @package         Better Preview
 * @version         6.7.1PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\BetterPreview\Component;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Article as RL_Article;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\BetterPreview\Params;

class Preview extends Helper
{
    var $errors = [];
    var $states = [];

    public function addMessages()
    {
        $html = JFactory::getApplication()->getBody();

        if ($html == '')
        {
            return;
        }

        // Set the category description if original description is empty
        // Need to do this because the onContentPrepare is not triggered when the description is empty
        if (JFactory::getApplication()->input->get('view') == 'category')
        {
            $empty_cat = '(<div class="category-desc[^"]*">)\s*(</div>)';
            if (RL_RegEx::match($empty_cat, $html))
            {
                $data = JFactory::getApplication()->input->get('previewdata', [], 'array');
                $this->urlDecode($data);
                if (isset($data['description']))
                {
                    $html = RL_RegEx::replace($empty_cat, '\1' . $data['description'] . '\2', $html);
                }
            }
        }

        $html = str_replace('</body>', '<div class="betterpreview_message">' . JText::_('BP_MESSAGE_PAGE') . '</div></body>', $html);

        if ( ! empty($this->errors))
        {
            $html = str_replace('</body>', '<div class="betterpreview_error">' . implode('<br>', $this->errors) . '</div></body>', $html);
        }

        JFactory::getApplication()->setBody($html);
    }

    public function checkSession()
    {
        $session_id = JFactory::getApplication()->input->get('session_id');
        $user_id    = JFactory::getApplication()->input->getInt('user');
        $client_id  = JFactory::getConfig()->get('shared_session', '0') ? 'IS NULL' : '= 1';

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('session_id'))
            ->from($db->quoteName('#__session'))
            ->where($db->quoteName('userid') . ' = ' . $user_id)
            ->where($db->quoteName('session_id') . ' = ' . $db->quote($session_id))
            ->where($db->quoteName('client_id') . ' ' . $client_id)
            ->order($db->quoteName('time') . ' DESC');
        $db->setQuery($query);
        $result = (string) $db->loadResult();

        return ($result && $result == $session_id);
    }

    public function diff($string_1, $string_2)
    {
        if (is_string($string_1) && ! is_string($string_2))
        {
            if ( ! is_array($string_2))
            {
                return true;
            }

            $string_1 = explode(',', $string_1);
        }

        if (is_array($string_1) && ! is_array($string_2))
        {
            return true;
        }

        if (is_object($string_1) && ! is_object($string_2))
        {
            return true;
        }

        if (is_array($string_2) || is_object($string_2))
        {
            foreach ($string_2 as $k => $v)
            {
                if ( ! $this->diff($string_1[$k], $v))
                {
                    continue;
                }

                return true;
            }

            return false;
        }

        if ( ! is_string($string_2))
        {
            return false;
        }

        $this->prepareString($string_1);
        $this->prepareString($string_2);

        return ($string_1 != $string_2);
    }

    public function getShowIntro(&$article)
    {
        if ($article && isset($article->params))
        {
            return $article->params->get('show_intro', '1');
        }

        return true;
    }

    public function getState($id, $table, $names = [], $isparent = false)
    {
        $names = (object) array_merge(
            [
                'id'        => 'id',
                'published' => 'published',
                'access'    => 'access',
                'parent'    => 'parent',
            ], $names
        );

        $data = JFactory::getApplication()->input->get('previewdata', [], 'array');
        $this->urlDecode($data);

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->from('#__' . $table . ' as a')
            ->where('a.' . $names->id . ' = ' . (int) $id);

        foreach ($names as $k => $v)
        {
            if ( ! $k || ! $v)
            {
                continue;
            }

            $query->select('a.' . $v . ' as ' . $k);
        }

        $db->setQuery($query);
        $item = $db->loadObject();

        if ( ! $item)
        {
            return;
        }

        $state = (object) [
            'table'     => $table,
            'id'        => $id,
            'published' => $item->published,
            'access'    => $item->access,
            'parent'    => $item->parent,
            'names'     => $names,
        ];

        if (isset($names->publish_up) && $item->publish_up > 0)
        {
            $state->publish_up = $item->publish_up;
        }

        if (isset($names->publish_down) && $item->publish_down > 0)
        {
            $state->publish_down = $item->publish_down;
        }

        if (isset($names->hits))
        {
            $state->hits = $item->hits;
        }

        $this->states[] = $state;

        if ( ! empty($this->errors))
        {
            return;
        }

        $now = strtotime(JFactory::getDate()->format('Y-m-d H:i:s'));

        if (
            $item->published != 1
            || (isset($data['published']) && $data['published'] != 1)
            || (isset($item->publish_up) && $item->publish_up > 1 && strtotime($item->publish_up) > $now)
            || (isset($data['publish_up']) && $data['publish_up'] > 1 && strtotime($data['publish_up']) > $now)
            || (isset($item->publish_down) && $item->publish_down > 1 && strtotime($item->publish_down) < $now)
            || (isset($data['publish_down']) && $data['publish_down'] > 1 && strtotime($data['publish_down']) < $now)
        )
        {
            $this->errors['BP_MESSAGE'] = $isparent ? JText::_('BP_MESSAGE_PARENT_UNPUBLISHED') : JText::_('BP_MESSAGE_ITEM_UNPUBLISHED');

            return;
        }

        if ($item->access != 1 || (isset($data['access']) && $data['access'] != 1))
        {
            $this->errors['BP_MESSAGE'] = $isparent ? JText::_('BP_MESSAGE_PARENT_ACCESS') : JText::_('BP_MESSAGE_ITEM_ACCESS');

            return;
        }
    }

    public function getText($article)
    {
        if ($this->getShowIntro($article) && isset($article->introtext) && isset($article->fulltext))
        {
            return $article->introtext . ' ' . $article->fulltext;
        }

        if ( ! empty($article->fulltext))
        {
            return $article->fulltext;
        }

        if ( ! empty($article->introtext))
        {
            return $article->introtext;
        }

        return $article->text;
    }

    public function getTextFromDB($article)
    {
        $db_article = RL_Article::get($article->id, true, ['a.introtext', 'a.fulltext']);

        if ( ! $db_article)
        {
            return '';
        }

        if ($this->getShowIntro($article))
        {
            return $db_article->introtext . ' ' . $db_article->fulltext;
        }

        if ( ! empty($article->fulltext))
        {
            return $db_article->fulltext;
        }

        return $db_article->introtext;
    }

    public function initStates($item_name = 'content', $item_states = [], $parent_name = 'categories', $parent_states = [])
    {
        $this->getState(
            JFactory::getApplication()->input->get('id'),
            $item_name,
            $item_states
        );

        $item = $this->states[count($this->states) - 1];

        while ($item->parent != 0)
        {
            $this->getState(
                $item->parent,
                $parent_name,
                $parent_states,
                true
            );

            $item = $this->states[count($this->states) - 1];
        }

        $this->setStates();
    }

    public function prepareValueByKey(&$val, $key, &$obj)
    {
        if (is_array($val) && $val == [0])
        {
            unset($val);

            return;
        }

        if (property_exists($obj, $key))
        {
            $v = $obj->{$key};
            switch ($v)
            {
                case null:
                    if (empty($val))
                    {
                        $val = $v;
                    }
                    break;
                case '0000-00-00 00:00:00':
                    if ($val == '')
                    {
                        $val = $v;
                    }
                    break;
                default:
                    break;
            }
            if (is_bool($v))
            {
                $val = $val ? true : false;
            }
        }
    }

    public function purgeCache()
    {
        $params = Params::get();

        if ( ! $params->purge_component_cache
            || ! $option = JFactory::getApplication()->input->get('option')
        )
        {
            return;
        }

        try
        {
            JFactory::getCache($option)->clean();
        }
        catch (Exception $e)
        {
            // ignore
        }
    }

    public function render(&$article, $context)
    {
        JHtml::_('jquery.framework');

        RL_Document::script('betterpreview/preview.min.js', '6.7.1.p');
        RL_Document::style('betterpreview/preview.min.css', '6.7.1.p');

        $has_changes = false;
        $data        = JFactory::getApplication()->input->get('previewdata', [], 'array');
        $this->urlDecode($data);

        $textpre = $this->getTextFromDB($article);

        if (isset($data['attribs']) && ! isset($data['params']))
        {
            $data['params'] = $data['attribs'];
        }

        // Set created_by_alias when created_by is set to other user
        if (
            empty($data['created_by_alias'])
            && ! empty($data['created_by'])
            && ! empty($article->created_by)
            && $data['created_by'] != $article->created_by
        )
        {
            $data['created_by_alias'] = JFactory::getUser($data['created_by'])->name;
        }

        // Boy, I hate this code with nested ifs.
        // This needs to get refactored!
        foreach ($data as $key => $val)
        {
            // ignore nonexistent fields
            if ( ! isset($article->{$key}))
            {
                continue;
            }

            // ignore crappy tags
            // and dynamic/unsettable fields
            // and date fields (because offset checking is a hell)
            if (in_array($key, [
                'tags',
                'published', 'state', 'access', 'hits', 'version',
                'created', 'modified', 'publish_up', 'publish_down',
            ]))
            {
                continue;
            }

            $objects = ['params', 'attribs', 'metadata', 'urls', 'images'];

            if (in_array($key, $objects))
            {
                if (is_object($article->{$key}))
                {
                    foreach ($val as $k => $v)
                    {
                        if (substr($k, 0, 5) === 'helix' || substr($k, 0, 13) === 'sppagebuilder')
                        {
                            $article->{$key}->remove($k);
                            unset($val[$k]);
                            continue;
                        }

                        if (is_string($v) && $v == '')
                        {
                            continue;
                        }

                        if ($this->diff($article->{$key}->get($k), $v))
                        {
                            $has_changes = true;
                        }

                        $article->{$key}->set($k, $v);
                    }

                    foreach ($article->{$key}->toArray() as $k => $v)
                    {
                        if (substr($k, 0, 5) === 'helix')
                        {
                            $article->{$key}->remove($k);
                            unset($val[$k]);
                            continue;
                        }
                    }

                    continue;
                }

                if (is_string($article->{$key}))
                {
                    $obj = (object) json_decode($article->{$key});
                    if (is_object($obj))
                    {
                        // force fields to null if originals are.
                        foreach ($val as $k => $v)
                        {
                            $this->prepareValueByKey($v, $k, $obj);

                            if (substr($k, 0, 5) === 'helix' || substr($k, 0, 13) === 'sppagebuilder')
                            {
                                unset($obj->{$k});
                                unset($val[$k]);
                                continue;
                            }

                            if ( ! isset($v))
                            {
                                unset($val[$k]);
                                continue;
                            }

                            $val[$k] = $v;
                        }

                        $val             = urldecode(json_encode($val));
                        $article->{$key} = json_encode($obj);
                    }
                }
            }
            else
            {
                $this->prepareValueByKey($val, $key, $article);
            }

            if ( ! in_array($key, ['introtext', 'fulltext', 'text']))
            {
                if ($this->diff($article->{$key}, $val))
                {
                    $has_changes = true;
                }
            }

            $article->{$key} = $val;
        }

        $article->text = $this->getText($article);

        // Fix weird issue with the video attribute being filled with the text on K2 items
        // @todo: find out why that happens
        if ($context == 'com_k2.item' && isset($article->video) && $article->video == $article->text)
        {
            // Set video value to data value or blank
            $article->video = $data['video'] ?? '';
        }

        if ($this->diff($article->text, $textpre))
        {
            $has_changes = true;
        }

        if ($has_changes)
        {
            $this->errors['BP_MESSAGE_HAS_CHANGES'] = JText::_('BP_MESSAGE_HAS_CHANGES');
        }
    }

    public function restoreState($state)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->update('#__' . $state->table)
            ->where($db->quoteName($state->names->id) . ' = ' . $db->quote($state->id));

        if (isset($state->names->published))
        {
            $query->set($db->quoteName($state->names->published) . ' = ' . $state->published);
        }

        if (isset($state->names->access))
        {
            $query->set($db->quoteName($state->names->access) . ' = ' . $state->access);
        }

        if (isset($state->names->hits))
        {
            $query->set($db->quoteName($state->names->hits) . ' = ' . $state->hits);
        }

        if (
            isset($state->names->publish_up)
            && isset($state->publish_up)
            && $state->publish_up > 0
        )
        {
            $query->set($db->quoteName($state->names->publish_up) . ' = ' . $db->quote($state->publish_up));
        }

        if (
            isset($state->names->publish_down)
            && isset($state->publish_down)
            && $state->publish_down > 0
        )
        {
            $query->set($db->quoteName($state->names->publish_down) . ' = ' . $db->quote($state->publish_down));
        }

        $db->setQuery($query);
        $db->execute();
    }

    public function restoreStates()
    {
        foreach ($this->states as $state)
        {
            $this->restoreState($state);
        }
    }

    public function setLanguage()
    {
        $language          = JFactory::getLanguage();
        $previous_language = JFactory::getApplication()->input->get('lang');

        if ($language->get('lang') != $previous_language && $language->exists($previous_language))
        {
            $language->setLanguage($previous_language);
        }
    }

    public function setState($state)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->update('#__' . $state->table)
            ->where($db->quoteName($state->names->id) . ' = ' . $db->quote($state->id));

        if (isset($state->names->published))
        {
            $query->set($db->quoteName($state->names->published) . ' = 1');
        }

        if (isset($state->names->access))
        {
            $query->set($db->quoteName($state->names->access) . ' = 1');
        }

        if (
            isset($state->names->publish_up)
            && isset($state->publish_up)
            && $state->publish_up > 0
        )
        {
            $query->set($db->quoteName($state->names->publish_up) . ' = DATE_SUB(CURDATE(), INTERVAL 2 DAY)');
        }

        if (
            isset($state->names->publish_down)
            && isset($state->publish_down)
            && $state->publish_down > 0
        )
        {
            $query->set($db->quoteName($state->names->publish_down) . ' = DATE_ADD(CURDATE(), INTERVAL 2 DAY)');
        }

        $db->setQuery($query);
        $db->execute();
    }

    public function setStates()
    {
        foreach ($this->states as $state)
        {
            $this->setState($state);
        }
    }

    public function states()
    {
    }

    public function urlDecode(&$data)
    {
        if (is_array($data) || is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $this->urlDecode($data[$k]);
            }
        }
        else if (is_string($data))
        {
            $data = urldecode($data);
        }
    }

    private function prepareString(&$string)
    {
        if ( ! is_string($string))
        {
            return;
        }

        $string = trim(RL_RegEx::replace('\s', '', $string));
        $string = RL_String::html_entity_decoder($string);
    }
}
