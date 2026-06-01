<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

jimport('joomla.form.formfield');
JFormHelper::loadFieldClass('usergroup');

/**
 * Recipients Field
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class JFormFieldLogmanrecipients extends JFormField
{
    public function getInput()
    {

        if (!class_exists('Koowa')) {
            return '';
        }

        $manager = KObjectManager::getInstance();

        //$manager->getObject('translator')->load('com://admin/logman');

        if (isset($this->element['id'])) {
            $id = (string) $this->element['id'];
        } else {
            $id = 'jform_'.$this->element['name'];
        }

        $value = $this->value;

        // Legacy support.
        if (is_string($value))
        {
            $value = trim($value);

            if (empty($value)) {
                $value = array();
            } else {
                $value = explode(',', $value);
            }
        }

        $options = array();

        foreach ($value as $recipient) {
            $options[] = (object) array('value' => $recipient, 'label' => $recipient);
        }

        $name = $this->name;

        $view     = KObjectManager::getInstance()->getObject('com://admin/logman.view.default.html');
        $template = $view->getTemplate()
                            ->addFilter('style')
                            ->addFilter('asset')
                            ->addFilter('script');

        $string = "
            <?= helper('ui.load', array('styles' => array('file' => 'component'))); ?>
            <?= helper('behavior.select2') ?>

            <?= helper('listbox.optionlist', array('selected' => \$selected, 'name' => \$name, 'attribs' => array('id' => \$id, 'multiple' => 'multiple'), 'options' => \$options)) ?>

            <script>
                kQuery(function($) {
                    $('#<?= \$id ?>').select2({
                      theme: 'bootstrap',
                      tags: true,
                      tokenSeparators: [',', ' ']});

                    $('#<?= \$id ?>').show();
                    $('#<?= \$id ?>_chzn').remove();
                });
            </script>
        ";

        return $template->loadString($string, 'php')->render(array(
            'selected'   => $value,
            'options' => $options,
            'id'      => $id,
            'name'    => $name
        ));
    }
}