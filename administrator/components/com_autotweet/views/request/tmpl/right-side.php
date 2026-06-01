<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$author = $this->item->xtform->get('author');
$title = $this->item->xtform->get('title');

if (($isManualMsg) && (empty($title))) {
    $this->item->xtform->set('title', '');
}

$fulltext = $this->item->xtform->get('fulltext');

if (($isManualMsg) && (empty($fulltext))) {
    $this->item->xtform->set('fulltext', '');
}

$allow_new_reqpost = EParameter::getComponentParam(CAUTOTWEETNG, 'allow_new_reqpost', 0);
$create_event = $this->item->xtform->get('create_event', 0);

if (EXTLY_J3) {
    require_once 'right-side.j3.php';
}

if (EXTLY_J4) {
    require_once 'right-side.j4.php';
}
