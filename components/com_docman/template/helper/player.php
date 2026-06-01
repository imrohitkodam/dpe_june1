<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateHelperPlayer extends ComFilesTemplateHelperPlayer
{
    /**
     * @param array $config
     * @return string html
     */
    public function render($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'document' => null,
            'asset'    => false
        ]);

        $document = $config->document;
        $asset    = $config->asset;

        $html = '';

        $template = $this->_getTemplate($asset);

        if ($this->_isYoutube($document)) {
            $html = $this->_renderYoutube($document, $template);
        }

        if ($this->_isVimeo($document)) {
            $html = $this->_renderVimeo($document, $template);
        }

        if ($this->isAudio($document)) {
            $html = $this->_renderAudio($document);
        }

        if ($this->isVideo($document)) {
            $html = $this->_renderVideo($document, $template);
        }

        return $html;
    }

    public function link($config = [])
    {
        $config = new KObjectConfigJson($config);

        $config->append([
            'document' => null,
        ]);

        $document = $config->document;

        $link = '';

        if ($document && ($url = $document->download_link))
        {
            $url = $this->getObject('lib:http.url', ['url' => $url]);

            $token = $this->getObject('lib:http.token')
                          ->setClaim('entity', $document->uuid)
                          ->setClaim('origin', $this->getObject('request')->getSiteUrl()->toString());

            $user = $this->getObject('user');

            if ($user->isAuthentic()) $token->setSubject($user->getEmail());

            $url->setQuery(array_merge(['auth_player' => $token->sign(JFactory::getConfig()->get('secret'))],$url->getQuery(true)));

            $link = $url->toString();
        }

        return $link;
    }

    protected function _getControls($document)
    {
        $controls = ['play', 'progress', 'duration', 'mute', 'volume', 'fullscreen'];

        if ($document->isLocal() && $document->canPerform('download')) $controls[] = 'download';

        return $controls;
    }

    /**
     * @param $document
     * @return string
     */
    public function getVideoId($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'document' => null
        ]);

        $document = $config->document;

        if ($this->_isYoutube($document)) {
            return $this->_getYoutubeId($document);
        }

        if ($this->_isVimeo($document)) {
            return $this->_getVimeoId($document);
        }

        return '';
    }

    /**
     * Get the template object
     *
     * @param boolean $asset Use to render the player outside the FW context
     *
     * @return KTemplateInterface
     *
     * @since version
     */
    protected function _getTemplate($asset = false)
    {
        $template = $this->getTemplate();

        if ($asset)
        {
            $template
                ->addFilter('script')
                ->addFilter('style');
        }

        return $template;
    }

    /**
     * @param $document
     * @return bool
     */
    protected function _isYoutube($document)
    {
        return $document->isYoutube();
    }

    /**
     * @param $document
     * @return string
     */
    protected function _getYoutubeId($document)
    {
        $url = parse_url($document->storage->path);

        if ($url)
        {
            if ($url['host'] === 'youtu.be') {
                return trim($url['path'], '/');
            }
            elseif (isset($url['query']))
            {
                parse_str($url['query'], $result);

                if (array_key_exists('v', $result)) {
                    return $result['v'];
                }
            }
        }

        return '';
    }

    /**
     * @param $document
     * @param $template
     *
     * @return string
     */
    protected function _renderYoutube($document, $template)
    {
        $video_id = $this->_getYoutubeId($document);

        if ($video_id === '') {
            return '';
        }

        $controls = $this->_getControls($document);

        $template->loadFile('com://site/docman.document.player_video_remote.html');

        return $template->render(array('service' => 'youtube', 'id' => $video_id, 'document' => $document, 'controls' => $controls));
    }

    /**
     * @param $document
     * @return bool
     */
    protected function _isVimeo($document)
    {
        return $document->isVimeo();
    }

    /**
     * @param $document
     * @return string
     */
    protected function _getVimeoId($document)
    {
        $video_id = substr(parse_url($document->storage->path, PHP_URL_PATH), 1);

        if ($video_id !== '') {
            return $video_id;
        }

        return '';
    }

    /**
     * @param $document
     * @param $template
     *
     * @return string
     */
    protected function _renderVimeo($document, $template)
    {
        $id = substr(parse_url($document->storage->path, PHP_URL_PATH), 1);

        if ($id == '') {
            return '';
        }

        $controls = $this->_getControls($document);

        $template->loadFile('com://site/docman.document.player_video_remote.html');

        return $template->render(array('service' => 'vimeo', 'id' => $id, 'document' => $document, 'controls' => $controls));
    }

    /**
     * @param $document
     * @return bool
     */
    public function isVideo($document)
    {
        if (in_array($document->extension, self::$_SUPPORTED_FORMATS['video'])) {
            return true;
        }

        return false;
    }

    /**
     * @param $document
     * @param $template
     *
     * @return string
     */
    protected function _renderVideo($document, $template)
    {
        $template->loadFile('com://site/docman.document.player_video_local.html');

        $controls = $this->_getControls($document);

        return $template->render(array('document' => $document, 'controls' => $controls));
    }

    /**
     * @param $document
     * @return bool
     */
    public function isAudio($document)
    {
        if (in_array($document->extension, self::$_SUPPORTED_FORMATS['audio'])) {
            return true;
        }

        return false;
    }

    /**
     * @param $document
     * @return string
     */
    protected function _renderAudio($document)
    {
        $controls = $this->_getControls($document);

        $html = $this->getTemplate()
                     ->loadFile('com://site/docman.document.player_audio_local.html')
                     ->render(array('document' => $document, 'controls' => $controls));

        return $html;
    }
}
