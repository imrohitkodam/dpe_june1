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

// This file is going to be deprecated on Perfect Publisher v10

// In the meantime, it just call to "perfect-publisher-cronjob.php".

// Please, rememeber to update the cron job with the new command line:
//
// php -f .../cli/perfect-publisher-cronjob.php

require_once 'perfect-publisher-cronjob.php';
