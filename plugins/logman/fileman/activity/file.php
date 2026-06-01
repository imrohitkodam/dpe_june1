<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * File/FILEman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanFilemanActivityFile extends PlgLogmanFilemanActivityNode
{
    protected function _initialize(KObjectConfig $config)
    {
        if ($config->data->metadata) {
            $format = '{actor} {action} {object.type} name {object} {target} {target.type}';
        } else {
            $format = '{actor} {action} {object.type} name {object}';
        }

        $config->append(array('format' => $format));

        parent::_initialize($config);
    }

    public function getPropertyImage()
    {
        if ($this->verb == 'add')
        {
            $image = 'k-icon-data-transfer-upload';
        }
        else if ($this->verb == 'download') {
            $image = 'k-icon-data-transfer-download';
        }
        else
        {
            $image = parent::getPropertyImage();
        }

        return $image;
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $url = null;

        $parts = parse_url($this->row);

        if (isset($parts['scheme']))
        {
            $path = trim($parts['path'], '/');

            $folder = dirname($path);

            $name = basename($path);

            if ($folder == '.') $folder = '';

            $url = $this->getObject('lib:http.url', array(
                'url' => '/administrator/index.php?option=com_fileman&view=file&routed=1&folder=' .
                         rawurlencode($folder) . '&name=' . rawurlencode($name) . '&container=' .
                         rawurlencode($parts['scheme'])
            ));

            $config->append(array('url' => array('admin' => $url)));


            if ($page = $this->_findPage($path))
            {
                $template = 'option=com_fileman&view=file&folder=%s&name=%s&Itemid=%s';
                $config->append(array('url' => array('site' => sprintf($template, rawurlencode($folder), rawurlencode($name), $page->id))));
            }

            $metadata = $this->getMetadata();

            if ($metadata->count())
            {
                $deleted = !$this->_findActivityObject();

                if ($metadata->image)
                {
                    $config->append(array('type' => array('objectName' => 'image', 'object' => true)));

                    if (!$deleted)
                    {
                        $config->append(array(
                            'image'      => array(
                                'url'    => $config->url->{$this->getContext()},
                                'width'  => $metadata->width,
                                'height' => $metadata->height
                            ),
                            'attributes' => array(
                                'data-width'  => $metadata->width,
                                'data-height' => $metadata->height
                            )
                        ));
                    }
                }

                if (!$deleted)
                {
                    $config->append(array(
                        'attributes' => array(
                            'class'     => 'fileman-file',
                            'data-name' => $metadata->name,
                            'data-size' => $metadata->size
                        )
                    ));
                }
            }
        }

        parent::_objectConfig($config);
    }

    public function getPropertyTarget()
    {
        $target = null;

        $metadata = $this->getMetadata();

        if ($metadata)
        {
            $url = $this->getObject('lib:http.url', array(
                'url' => '/administrator/index.php?option=com_' . $this->package . '&view=files&folder=' .
                         rawurlencode($metadata->folder) . '&container=' . $metadata->container->slug
            ));

            $url = array('admin' => $url);

            $folder = $metadata->folder;

            if ($page = $this->_findPage($folder))
            {

                $template    = 'option=com_fileman&view=folder&folder=%s&Itemid=%s';
                $url['site'] = sprintf($template, rawurlencode($folder), $page->id);
            }

            $target = $this->_getObject(array(
                'id'         => $metadata->container->slug . ':' . ($folder ? $folder : ''),
                'type'       => array('objectName' => 'folder', 'object' => true),
                'objectName' => $folder ? htmlspecialchars($folder) : $metadata->container->title,
                'find'       => 'target',
                'url'        => $url
            ));
        }

        return $target;
    }

    protected function _findActivityTarget()
    {
        $result = false;

        $metadata = $this->getMetadata();

        if ($metadata)
        {
            $container = $this->_getContainer($metadata->container->slug);

            if ($container) {
                $result = file_exists($container->fullpath . '/' . $metadata->folder);
            }
        }

        return $result;
    }

    protected function _getTargetSignature()
    {
        $signature = null;

        $metadata = $this->getMetadata();

        if ($metadata)
        {
            $signature = sprintf('%s.folder.%s:%s', $this->package, $metadata->container->slug, $metadata->folder);
        }

        return $signature;
    }

    public function getPropertyScripts()
    {
        $scripts = null;

        if ($this->_hasDependencies() && JFactory::getApplication()->isClient('administrator'))
        {
            $translator = $this->getObject('translator');

            $scripts = <<<EOD
<ktml:style src="media://plg_logman_fileman/css/fileman.css"/>
<ktml:script src="media://plg_logman_fileman/js/fileman.js"/>
<ktml:script src="media://koowa/com_files/js/ejs_utilities.min.js"/>
<script>
    kQuery(function() {
        PlgLogmanFileman.Lightbox.init();
    });
</script>
<textarea style="display: none" id="fileman-file-template">
<div class="activity-file-preview">
    [% if (typeof image !== 'undefined') {
        var ratio = 400 / (width > height ? width : height); %]
        <img src="[%=url%]" alt="[%=name%]" border="0" style="
             width: [%=Math.min(ratio*width, width)%]px;
             height: [%=Math.min(ratio*height, height)%]px
         "/>
    [% } else { %]
        <span class="k-icon-document-image"><i>[%=name%]</i></span>
    [% } %]

    <div class="btn-toolbar">
        [% if (typeof image !== 'undefined') { %]
        <a class="btn btn-mini btn-outline-secondary btn-sm" href="[%=url%]" target="_blank">
            <i class="k-icon-eye"></i>{$translator->translate('View')}
        </a>
        [% } else { %]
        <a class="btn btn-mini btn-outline-secondary btn-sm" href="[%=url%]" target="_blank" download="[%=name%]">
            <i class="k-icon-data-transfer-download"></i>{$translator->translate('Download')}
        </a>
        [% } %]
    </div>
</div>
<div class="activity-file-details">
    <table class="table table-condensed parameters">
        <tbody>
            <tr>
                <td class="detail-label">{$translator->translate('Name')}</td>
                <td>[%=name%]</td>
            </tr>
            <tr>
                <td class="detail-label">{$translator->translate('Size')}</td>
                <td>
                [%=new Files.Filesize(size).humanize()%]
                [% if (typeof image !== 'undefined') { %]
                    ([%=width%] x [%=height%])
                [% } %]
                </td>
            </tr>
        </tbody>
    </table>
</div>
</textarea>
<div id="fileman-file-tmp" style="display: none;">
</div>
EOD;
        }

        return $scripts;
    }

    protected function _actionConfig(KObjectConfig $config)
    {
        if ($this->verb == 'add')
        {
            $config->append(array('objectName' => 'uploaded'));
        }

        parent::_actionConfig($config);
    }
}
