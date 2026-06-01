<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Settings/Config Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanConfigActivityConfiguration extends ComLogmanModelEntityActivity
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        ComLogmanActivityTranslator::loadSysIni('com_config');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format' => '{actor} {action} {object.type} {object}'
        ));

        parent::_initialize($config);
    }

    public function getPropertyImage()
    {
        return 'k-icon-cog';
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array(
            'find'       => false,
            'objectName' => 'configuration',
            'url'        => array('admin' => '#'),
            'type'       => array('objectName' => 'site', 'object' => true)
        ));

        if (($metadata = $this->getMetadata()) && isset($metadata['changes']))
        {
            $changes = $metadata['changes'];

            $translator = $this->getObject('translator');

            $transtatable_fields = array('databaseconnection');

            foreach ($changes as $name => $data)
            {
                $changes[$name]['label'] = $translator->translate($changes[$name]['label']);

                if (isset($data['values'])) {
                    foreach ($data['values'] as $key => $value)
                    {
                        if (is_string($value['value'])) $value['value'] = trim($value['value']); // For displaying Empty instead of empty string

                        if (isset($value['label'])) {
                            $changes[$name]['values'][$key]['value'] = $translator->translate($value['label']);
                        }
                        elseif ($changes[$name]['type'] == 'plugins')
                        {
                            $plugin = $changes[$name]['values'][$key]['value'];
                            $folder = $changes[$name]['folder'];

                            $identifier = sprintf('plg_%s_%s',  $folder, $plugin);

                            // Load the translation file for the plugin
                            ComLogmanActivityTranslator::loadSysIni($identifier);

                            $translation_key = strtoupper($identifier);

                            $translation = $translator->translate($translation_key);

                            if ($translation != $translation_key) {
                                $changes[$name]['values'][$key]['value'] = $translation;
                            }
                        }
                        elseif (in_array($changes[$name]['type'], $transtatable_fields))
                        {
                            $translation_key = strtoupper($changes[$name]['values'][$key]['value']);

                            $translation = $translator->translate($translation_key);

                            if ($changes[$name]['values'][$key]['value'] != $translation_key) {
                                $changes[$name]['values'][$key]['value'] = $translation;
                            }
                        }

                        if (is_null($value['value'])) {
                            $changes[$name]['values'][$key]['value'] = $translator->translate('Not set'); // For null values
                        }

                        if (!is_numeric($value['value']) && !is_null($value['value']) && empty($value['value'])) {
                            $changes[$name]['values'][$key]['value'] = $translator->translate('Empty'); // For empty strings
                        }
                    }
                }
            }

            $config->append(array(
                'attributes' => array(
                    'class'        => 'logman_activity_config_toggle',
                    'data-changes' => json_encode($changes)
                )
            ));
        }

        parent::_objectConfig($config);
    }

    public function getPropertyScripts()
    {
        $metadata = $this->getMetadata();

        if (isset($metadata['changes']))
        {
            $scripts = "
                <ktml:style src=\"media://plg_logman_config/css/config.css\"/>
                <ktml:script src=\"media://koowa/com_files/js/ejs_utilities.min.js\"/>
                <script>
                    kQuery(function($) {
                        $('.logman_activity_config_toggle').click(function(e)
                        {                             
                            e.preventDefault();
                                                                   
                            var rendered = new EJS({element: document.getElementById('logman-config-activity-template')}).render({changes: $(this).data('changes')}),
                                element = $('<div class=\"k-ui-namespace logman_activity_config_modal\"></div>').append(rendered),
                                container = $('#logman-config-tmp'),
                                output = element.appendTo(container);
                
                            var display = function() {
                                output.css('max-width', container.width());
                                $.magnificPopup.open({items: {type: 'inline', src: output}});
                                container.empty();
                            };
                
                            setTimeout(display, 100); 
                        });
                    });
                </script>
                <textarea style=\"display: none\" id=\"logman-config-activity-template\">
                    <div class=\"k-table-container logman_activity_config_changes\">
                        <div class=\"k-table\">
                            <table>
                                <thead>
                                    <tr>             
                                        <th class=\"k-font-strong\">Name</th>
                                        <th class=\"k-font-strong\">Old value</th>
                                        <th class=\"k-font-strong\">New value</th>
                                    </tr>
                                    </thead>
                                <tbody>
                                    [% Object.keys(changes).forEach(function(name) { %]
                                    <tr>                      
                                        <td>
                                            [%= changes[name].label %]
                                        </td>
                                        <td>
                                        [% if (changes[name].values) { %]
                                        
                                        [% if (changes[name].type == 'radio') { %]
                                            [% if (changes[name].values.old.label == 'JYES') { %]
                                                <span class=\"k-color-success k-font-strong\">[%= changes[name].values.old.value %]</span>
                                            [% } else { %]
                                                <span class=\"k-color-error k-font-strong\">[%= changes[name].values.old.value %]</span>                          
                                            [% } %]         
                                        [% } else { %]
                                            [%= changes[name].values.old.value %]
                                        [% } %]  
                                        
                                        [% } %]                          
                                        </td>
                                        <td>
                                        [% if (changes[name].values) { %]
                                        [% if (changes[name].type == 'radio') { %]
                                            [% if (changes[name].values.new.label == 'JYES') { %]
                                                <span class=\"k-color-success k-font-strong\">[%= changes[name].values.new.value %]</span>
                                            [% } else { %]
                                                <span class=\"k-color-error k-font-strong\">[%= changes[name].values.new.value %]</span>               
                                            [% } %]         
                                        [% } else { %]
                                            [%= changes[name].values.new.value %]
                                        [% } %] 
                                        [% } %]    
                                        </td>
                                    </tr>
                                    [% }); %]
                                </tbody>
                            </table>
                        </div>
                    </div>
                </textarea>
                <div id=\"logman-config-tmp\" style=\"display: none;\"></div>";
        }
        else $scripts = '';

        return $scripts;
    }
}