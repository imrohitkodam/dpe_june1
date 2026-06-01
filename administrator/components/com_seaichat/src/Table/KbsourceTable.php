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

class KbsourceTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__seaichat_kb_sources', 'id', $db);
    }

    public function check(): bool
    {
        if (empty($this->title)) {
            $this->setError('Title is required.');
            return false;
        }

        if (empty($this->source_type)) {
            $this->source_type = 'url';
        }

        if ($this->source_type === 'url') {
            if (empty($this->url)) {
                $this->setError('URL is required for website sources.');
                return false;
            }
            // Ensure URL has protocol
            if (!preg_match('#^https?://#i', $this->url)) {
                $this->url = 'https://' . $this->url;
            }
        }

        return true;
    }
}
