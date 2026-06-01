<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateFilterAsset extends ComKoowaTemplateFilterAsset
{
    protected function _initialize(KObjectConfig $config)
    {
        $icon_path = $this->getObject('com:files.model.containers')->slug('docman-icons')->fetch()->path;
        $path      = rtrim($this->getObject('request')->getSiteUrl()->getPath(), '/');

        $config->append(array(
            'schemes' => array(
                'icon://' => $path.'/'.$icon_path.'/'
            ),
        ));

        parent::_initialize($config);
    }
}