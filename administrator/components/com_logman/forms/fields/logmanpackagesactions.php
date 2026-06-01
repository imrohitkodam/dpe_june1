<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

jimport('joomla.form.formfield');

/**
 * Packages Actions Field
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class JFormFieldLogmanpackagesactions extends JFormField
{
    public function __construct($form = null)
    {
        parent::__construct($form);
    }

    public function getName($fieldName)
    {
        $this->multiple = false;

        $name = parent::getName($fieldName);
        $name .= '[packages][]';

        return $name;
    }

    public function getInput()
    {
        if (!class_exists('Koowa')) {
            return '';
        }

        $manager = KObjectManager::getInstance();

        $translator = $manager->getObject('translator');

        if (isset($this->element['id'])) {
            $id = (string) $this->element['id'];
        } else {
            $id = 'jform_'.$this->element['name'];
        }

        $value = isset($this->value['packages']) ?  $this->value['packages'] : (empty($this->value) ? array() : $this->value);

        $name  = $this->name;

        $view     = KObjectManager::getInstance()->getObject('com://admin/logman.view.default.html');
        $template = $view->getTemplate()
                            ->addFilter('style')
                            ->addFilter('asset')
                            ->addFilter('script');

        $string = "
        <?= helper('ui.load', array('styles' => array('file' => 'component'))); ?>

        <?= helper('com://admin/logman.listbox.packages', array(
            'attribs' => array(
                'id' => \$id,
                'multiple' => true),
            'api' => true,
            'prompt'   => \$prompt,
            'deselect' => true,
            'name'     => \$name,
            'selected' => \$selected
        )); ?>";

        $format = '[%s][%s][]';

        $select_name  = str_replace(sprintf($format, $this->fieldname, 'packages'), sprintf($format, $this->fieldname, 'actions'), $name);
        $select_value = isset($this->value['actions']) ? $this->value['actions'] : array();

        $string .= "
        <script>
            kQuery(function($){
                $('#<?= \$id ?>').show();
                $('#<?= \$id ?>_chzn').remove();

                var generateSelect = function(data)
                {
                    var select = $('<select multiple=\"multiple\" id=\"logman_actions\" name=\"" . $select_name . "\"></select>');

                    $.each(data, function(idx, option) {
                        select.append('<option value=\"' + option.id + '\" >' + option.label + '</option>');
                    });

                    return select;
                }

                var selectHandler = function(el, actions)
                {
                       var parent = el.parent('.k-ui-container.com_logman');

                       var package = el.val();

                        if (package && package.length === 1) {
                            // Show

                            var url = \"<?= JRoute::_('index.php?option=com_logman&view=plugins&format=json&api=true&layout=actions', false) ?>\";
                            url += '&package=' + package[0];

                            $.ajax({
                                url: url,
                                success: function(data) {
                                    var select = generateSelect(data);
                                    parent.append(select);

                                    select.select2({theme: 'bootstrap', placeholder: '<?= translate(\"All actions\") ?>', allowClear: true});

                                    if (actions) {
                                        select.val(actions).trigger('change');
                                    }
                                }
                            });
                        }
                        else parent.find('#logman_actions').select2('destroy').remove();
                };

                $('#<?= \$id ?>').on('select2:select', function(e)
                {
                       selectHandler($(this));
                });

                $('#<?= \$id ?>').on('select2:unselect', function(e)
                {
                       selectHandler($(this));
                });

                if (" .(int) (count($value) === 1) . ") {
                    selectHandler($('#<?= \$id ?>'), " . json_encode($select_value) . "); // Initial selection

                }
            });
        </script>
        ";

        return $template->loadString($string, 'php')->render(
            array(
                'name'     => $name,
                'selected' => $value,
                'id'       => $id,
                'prompt'   => $translator->translate('All components'),
            ));
    }
}