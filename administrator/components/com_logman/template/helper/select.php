<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Select Tempplate Helper
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanTemplateHelperSelect extends KTemplateHelperSelect
{
    /**
     * Actions Checklist.
     *
     * @param array $config An optional configuration array.
     * @return string The actions checklist.
     */
    public function actions($config = array())
    {
        $config = new KObjectConfigJson($config);

        $config->append(array(
            'actions' => array(),
            'name'      => 'actions',
            'selected'  => null,
        ))->append(array('id' => $config->name));

        $translator = $this->getObject('translator');
        $attribs    = $this->buildAttributes($config->attribs);

        $html = array();

        $html[] = '<fieldset id="'.$config->id.'" name="'. $config->name .'">';

        foreach($config->actions as $resource => $actions)
        {
            $resource = (string) $resource;

            if ($actions instanceof KObjectConfigInterface && count($actions))
            {
                $legend = $translator->translate(ucfirst($resource));

                $html[] = '<legend>' . $legend . '</legend>';

                foreach ($actions as $action)
                {
                    $label = $translator->translate(ucfirst($action));

                    $extra = '';

                    if ($config->selected instanceof KObjectConfig)
                    {
                        $selected = $config->selected->{$resource};

                        if ($selected && in_array($action, KObjectConfig::unbox($selected))) {
                            $extra .= 'checked="checked"';
                        }
                    }

                    $html[] = '<label class="checkbox inline" for="'.$resource.'_'.$action.'">';
                    $html[] = '<input type="checkbox" name="'.$config->name.'['.$resource.'][]" id="'.$resource.'_'.$action.'" value="'.$action.'" '.$extra.' '.$attribs.' />';
                    $html[] = $label;
                    $html[] = '</label>';
                }
            }
        }

        $html[] = '</fieldset>';

        return implode(PHP_EOL, $html);
    }
}