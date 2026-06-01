<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// @deprecated  1.3.32 Use TJCertificate certificate view instead
// No direct access
defined('_JEXEC') or die;
$comtjlmsHelper = new comtjlmsHelper;

$pdfname1 = 'Certificate_' . $this->userid . '_' . $this->course_id . ".pdf";
$pdfname = JPATH_SITE . "/libraries/techjoomla/dompdf/tmp/" . $pdfname1;

$pdffile = $comtjlmsHelper->generatepdf($this->html, $pdfname, $this->course_id, $this->userid, 1);

echo $pdffile;
die;
