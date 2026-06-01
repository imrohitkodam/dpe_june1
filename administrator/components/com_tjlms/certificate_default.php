<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// @deprecated  1.3.32 Use TJCertificate template view instead
// No direct access.
defined('_JEXEC') or die;

$certificate = array(
'message_body' => "
<table width='100%'>
<tbody>
<tr>
<td style='border: 10px solid #787878; padding: 20px;'>
<table width='100%'>
<tbody>
<tr>
<td style='border: 5px solid #787878;'>
<table width='100%' cellpadding='5'>
<tbody>
<tr>
<td align='center'>
<h1>Certificate of Completion</h1>
</td>
</tr>
<tr>
<td align='center'>
<h3 style='font-weight: normal;'><i>This is to certify that</i></h3>
</td>
</tr>
<tr>
<td align='center'>
<h2>[STUDENTNAME]</h2>
</td>
</tr>
<tr>
<td align='center'>
<h3 style='font-weight: normal;'><i> has completed the course</i></h3>
</td>
</tr>
<tr>
<td align='center'>
<h2><b>[COURSE]</b></h2>
</td>
</tr>
<tr>
<td align='center'>
<h3 style='font-weight: normal;'><i> dated </i></h3>
</td>
</tr>
<tr>
<td align='center'>
<h2 style='font-weight: normal;'>[GRANTED_DATE]</h2>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
"
);
