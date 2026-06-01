<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel as JListModel;
use RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Helper\ExtensionsHelper;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\Parameters as RL_Parameters;

defined('_JEXEC') or die;

class DiscoverModel extends JListModel
{
    protected $config;

    /**
     * @var     string    The prefix to use with controller messages.
     */
    protected $text_prefix = 'RL';

    /**
     * Constructor.
     *
     * @param array    An optional associative array of configuration settings.
     *
     * @see        JController
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->config = RL_Parameters::getComponent('regularlabsmanager');
    }

    public function getItems($refresh = false)
    {
        $refresh = $refresh ?: (bool) RL_Input::getInt('refresh', 0);

        return ExtensionsHelper::get($refresh);
    }
}
