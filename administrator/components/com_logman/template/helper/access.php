<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanTemplateHelperAccess extends KTemplateHelperAbstract
{
    public function rules($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'component' => 'com_logman',
            'section' => 'component',
            'name' => 'rules',
            'asset' => null,
            'asset_id' => 0
        ))->append(array(
            'id' => $config->name
        ));

        $xml = <<<EOF
<form>
    <fieldset>
        <field name="asset_id" type="hidden" value="{$config->asset_id}" />
        <field name="{$config->name}" type="rules" label="JFIELD_RULES_LABEL"
            translate_label="false" class="inputbox" filter="rules"
            component="{$config->component}" section="{$config->section}" validate="rules"
            id="{$config->id}"
        />
    </fieldset>
</form>
EOF;

        $form = JForm::getInstance('com_logman.acl', $xml);
        $form->setValue('asset_id', null, $config->asset_id);

        $html = $form->getInput('rules');

        // Do not allow AJAX saving - it tries to guess the asset name with no way to override
        $html = preg_replace('#onchange="sendPermissions[^"]*"#i', '', $html);

        return $html;
    }
}
