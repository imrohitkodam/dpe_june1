<?php

/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */
namespace RegularLabs\Library;

defined('_JEXEC') or die;
use JLoader;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin as JFieldsPlugin;
use ReflectionClass;
/*
 * @deprecated: use RegularLabs\Library\Plugin\Fields instead
 */
class FieldsPlugin extends JFieldsPlugin
{
    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);
        $path = JPATH_PLUGINS . '/fields/' . $this->_name . '/src/Form/Field';
        if (!file_exists($path)) {
            return;
        }
        $name = $this->getClassName();
        JLoader::registerAlias('JFormField' . $name, '\RegularLabs\Plugin\Fields\\' . $name . '\Form\Field\\' . $name . 'Field');
    }
    private function getClassName(): string
    {
        $name = (new ReflectionClass($this))->getShortName();
        $name = str_replace('PlgFields', '', $name);
        $name = str_replace('J4', '', $name);
        return $name;
    }
}
