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
 * User Group Field
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class JFormFieldLogmanusergroups extends JFormField
{
    protected $type = 'Logmanusergroups';

    public function getInput()
    {
        if (!class_exists('Koowa')) {
            return '';
        }

        $translator = KObjectManager::getInstance()->getObject('translator');

        $id = 'logman_usergroups';

        if (isset($this->element['id'])) {
            $id = (string) $this->element['id'];
        }

        $placeholder = $translator->translate(isset($this->element['placeholder']) ? (string)$this->element['placeholder'] : 'Select user groups');

        $multiple = false;

        if (isset($this->element['multiple'])) {
            $multiple = ((string) $this->element['multiple']) == 'true' ? true : false;
        }

        $deselect = false;

        if (isset($this->element['deselect'])) {
            $deselect = ((string) $this->element['deselect']) == 'true' ? true : false;
        }

        if ($multiple) {
            $deselect = true;
        }

        //KObjectManager::getInstance()->getObject('translator')->load('com://admin/logman');

        $view = KObjectManager::getInstance()->getObject('com://admin/logman.view.default.html');
        $template = $view->getTemplate()
            ->addFilter('style')
            ->addFilter('script');

        $string = "
        <?= helper('ui.load', array(
            'styles' => array('file' => 'component'),
            'wrapper_class' => array('usergroups-select')
        )); ?>

        <?= helper('listbox.usergroups', array(
            'name' => \$name,
            'selected' => \$selected,
            'deselect' => \$deselect,
            'prompt' => \$placeholder,
            'attribs' => array(
                'multiple' => \$multiple,
                'id' => \$id
            )
        )); ?>";

        $string .= "
        <script>
            kQuery(function($){
                $('#s2id_<?= \$id ?>').show();
                $('#<?= \$id ?>_chzn').remove();
            });
        </script>
        ";

        $template->loadString($string, 'php')
                 ->render(array(
                     'placeholder' => $placeholder,
                     'id'          => $id,
                     'deselect'    => $deselect,
                     'name'        => $this->name,
                     'selected'    => $this->value,
                     'multiple'    => $multiple
                 ));

        return $template->render();
    }
}