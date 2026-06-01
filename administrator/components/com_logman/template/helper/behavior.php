<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Behavior Tempplate Helper
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanTemplateHelperBehavior extends ComKoowaTemplateHelperBehavior
{
    /**
     * Loads LOGman's JS library.
     *
     * @param array $config An optional configuration array.
     * @return string HTML containing library loading calls.
     */
    public function logman($config = array())
    {
        $html = '';

        if (!isset(self::$_loaded['logman-js']))
        {
            $html .= '<ktml:script src="media://com_logman/js/logman.js" />';
            self::$_loaded['logman-js'] = true;
        }

        return $html;
    }

    public function component_tree($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'element'  => '.k-js-component-tree',
            'packages' => array(),
            'options' => array(
                'autoOpen' => true,
                'lang' => array(
                    'root' => $this->getObject('translator')->translate('All components')
                )
            ),
        ));

        $data = array();
        $translator = $this->getObject('translator');

        $data[] = array(
            'label'  => $translator->translate('All components'),
            'id'     => 'root',
            'path'   => 'root',
            'href'   => (string)$this->getTemplate()->route('package='),
            'level'  => 0,
            'parent' => ''
        );

        foreach ($config->packages as $package)
        {
            $data[] = array(
                'label' => $translator->translate(ucfirst($package)),
                'parent' => 'root',
                'id'    => $package,
                'path'  => $package,
                'href'  => (string)$this->getTemplate()->route('package='.$package),
                'level' => 1
            );
        }

        $config->options->data = $data;

        $html = parent::tree($config);

        $html .= "<script>
        kQuery(function($) {
            $('{$config->element}').bind('tree.click', function(event) {
                event.preventDefault();
                
                window.location.href = event.node.href;
            });
        });
        </script>";

        return $html;
    }
}