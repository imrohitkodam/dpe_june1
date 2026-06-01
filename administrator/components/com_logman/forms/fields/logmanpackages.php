<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

jimport('joomla.form.formfield');

/**
 * Packages Field
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class JFormFieldLogmanpackages extends JFormField
{
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

        $value = $this->value;
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
            'prompt'   => \$prompt,
            'deselect' => true,
            'name'     => \$name,
            'selected' => \$selected
        )); ?>";

        $string .= "
        <script>
            kQuery(function($){
                $('#s2id_<?= \$id ?>').show();
                $('#<?= \$id ?>_chzn').remove();
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