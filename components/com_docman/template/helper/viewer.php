<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
class ComDocmanTemplateHelperViewer extends KTemplateHelperAbstract
{
    /**
     * Render embedded element
     * 
     * @param array $config
     * @return string html
     */
    public function render($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'document' => null
        ]);

        $document = $config->document;

        $html = '';

        if ($document->storage->extension == 'pdf') {
            $html = $this->_renderPdf($document);
        } elseif ($document->isDocument()) {
            $html = $this->_renderDocument($document);
        } elseif ($document->isImage()) {
            $html = $this->_renderImage($document);
        }

        return $html;
    }

    /**
     * Render document embed
     * 
     * @param KModelEntityInterface $document
     * @return string
     */
    protected function _renderDocument($document)
    {
        $template = $this->getTemplate();
        $template->loadFile('com://site/docman.document.viewer_document.html');

        $resource = $document->getPermalink(array('auth_policy' => 'com://admin/docman.controller.permission.yesman')) . '?force_download=1';

        $url = sprintf('https://docs.google.com/viewer?url=%s&embedded=true', urlencode($resource));

        return  $template->render(['url' => $url, 'id'  => 'doc-viewer']);
    }

    /**
     * Render pdf embed
     * 
     * @param KModelEntityInterface $document
     * @return string
     */
    protected function _renderPdf($document)
    {
        $template = $this->getTemplate();
        $template->loadFile('com://site/docman.document.viewer_document.html');

        $url = sprintf('https://connect.joomlatools.com/pdf-viewer/?file=%s', urlencode($document->getPermalink(array('auth_policy' => 'com://admin/docman.controller.permission.yesman'))));
                        
        if ($document->canPerform('download')) {
            $buttons = array('show_download' => 1, 'show_print' => 1);
        } else {
            $buttons = array('show_download' => 0, 'show_print' => 0);
        }

        $url = $url . '&' . http_build_query($buttons);

        return  $template->render(['url' => $url, 'id'  => 'pdf-js-viewer']);
    }
    
    /**
     * Render image embed
     * 
     * @param KModelEntityInterface $document
     * @return string
     */
    protected function _renderImage($document)
    {
        $template = $this->getTemplate();
        $template->loadFile('com://site/docman.document.viewer_image.html');

        $url = $document->image_download_path;

        return  $template->render(['url' => $url, 'title' => $document->title]);
    }
}