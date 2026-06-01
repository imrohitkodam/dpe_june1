<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace SolarEclipse\Component\SeAiChat\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class CalltoactionTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__seaichat_ctas', 'id', $db);
    }

    public function check(): bool
    {
        if (empty($this->title)) {
            $this->setError('Title is required.');
            return false;
        }

        if (empty($this->keywords)) {
            $this->setError('At least one keyword is required.');
            return false;
        }

        if (empty($this->button_label)) {
            $this->setError('Button label is required.');
            return false;
        }

        if (empty($this->button_url)) {
            $this->setError('Button URL is required.');
            return false;
        }

        if (empty($this->button_icon)) {
            $this->button_icon = 'fa-arrow-up-right-from-square';
        }

        if (empty($this->button_target)) {
            $this->button_target = '_self';
        }

        return true;
    }
}
