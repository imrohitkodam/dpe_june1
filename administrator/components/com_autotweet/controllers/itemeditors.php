<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

require_once 'default.php';

/**
 * AutotweetControllerItemEditors.
 *
 * @since       1.0
 */
class AutotweetControllerItemEditors extends AutotweetControllerDefault
{
    /**
     * Single record add. The form layout is used to present a blank page.
     *
     * @return false|void
     */
    public function add()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        parent::add();
    }
}
