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
namespace RegularLabs\Library\Plugin;

defined('_JEXEC') or die;
use Bluecoder\Component\Jfilters\Administrator\Model\Filter\Option\Collection as JfiltersCollection;
use JLoader;
use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin as JFieldsPlugin;
use Joomla\Event\DispatcherInterface as JDispatcherInterface;
use Joomla\Event\SubscriberInterface;
use ReflectionClass;
use stdclass;
class Fields extends JFieldsPlugin implements SubscriberInterface
{
    static $_extra_events = [];
    public function __construct($config = [])
    {
        if ($config instanceof JDispatcherInterface) {
            $dispatcher = $config;
            $config = func_num_args() > 1 ? func_get_arg(1) : [];
            parent::__construct($dispatcher, $config);
        } else {
            parent::__construct($config);
        }
        $path = JPATH_PLUGINS . '/fields/' . $this->_name . '/src/Form/Field';
        if (!file_exists($path)) {
            return;
        }
        $name = str_replace('PlgFields', '', $this->getClassName());
        JLoader::registerAlias('JFormField' . $name, '\RegularLabs\Plugin\Fields\\' . $name . '\Form\Field\\' . $name . 'Field');
    }
    public static function getSubscribedEvents(): array
    {
        return ['onCustomFieldsGetTypes' => 'getFieldTypes', 'onCustomFieldsPrepareField' => 'prepareField', 'onCustomFieldsPrepareDom' => 'prepareDom', 'onContentPrepareForm' => 'prepareForm', 'onJFiltersOptionsAfterCreation' => 'onJFiltersOptionsAfterCreation', ...static::$_extra_events];
    }
    public function onJFiltersOptionsAfterCreation(AbstractImmutableEvent $event): void
    {
        $options = $event->getArgument('collection');
        if ($options->getFilterItem()->getAttributes()->get('type') !== $this->_name) {
            return;
        }
        $this->handleOnJFiltersOptionsAfterCreation($options);
    }
    /**
     * @param string   $context The context.
     * @param stdclass $item    The item.
     * @param stdclass $field   The field.
     *
     * @return  ?string
     */
    public function onCustomFieldsPrepareField($context, $item, $field)
    {
        // Check if the field should be processed by us
        if (!$this->isTypeSupported($field->type)) {
            return '';
        }
        // The field's rawvalue should be an array
        if (!is_array($field->rawvalue)) {
            $field->rawvalue = (array) $field->rawvalue;
        }
        $this->handleOnPrepareField($context, $item, $field);
        return parent::onCustomFieldsPrepareField($context, $item, $field);
    }
    protected function handleOnJFiltersOptionsAfterCreation(JfiltersCollection $options): void
    {
    }
    protected function handleOnPrepareField(string $context, object $item, object $field): void
    {
    }
    private function getClassName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
