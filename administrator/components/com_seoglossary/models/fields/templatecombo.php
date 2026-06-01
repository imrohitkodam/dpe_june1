<?php
/**
 * SEO Glossary Templatepicker field
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access to this file
defined('_JEXEC') or die;

jimport('joomla.form.helper');
jimport( 'joomla.form.fields.combo' );
JFormHelper::loadFieldClass('list');
JFormHelper::loadFieldClass('combo');

class JFormFieldTemplateCombo extends JFormFieldCombo {

    public $type = 'TemplateCombo';

    protected function getOptions() {
        $doc = jfactory::getdocument();
        $doc->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/style.css');
        
        $folder = JPATH_SITE . '/components/com_seoglossary/templates';
        $templates = scandir($folder);

        jimport('joomla.plugin.helper');
        if (JPluginHelper::isEnabled('seoglossary', 'plg_templates_seoglossary')) {
            $folder = JPATH_SITE . '/plugins/seoglossary/plg_templates_seoglossary/templates';
            $templates = array_merge($templates, scandir($folder));
        }

        $options = array();
        
        foreach($templates as $template) {
            if ($template == '.' || $template == '..' || stripos($template, 'index.html') !== FALSE || stripos($template, '.') !== FALSE)
                continue;

            $options[] = JHtml::_('select.option', $template, $template);

        }
            $options[] = JHtml::_('select.option', 'grid','grid');

        return $options;
    }

    protected function getInput()
    {
        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= ((string) $this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
        $attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $attr .= $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '';

        // Get the field options.
        $options = $this->getOptions();

        // Load the combobox behavior.
        JHtml::_('behavior.combobox');

        // Build the list for the combo box.
        $html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);

        return implode($html);
    }
}
