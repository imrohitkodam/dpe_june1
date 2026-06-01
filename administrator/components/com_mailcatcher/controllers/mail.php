<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

class MailcatcherControllerMail extends JControllerForm
{
	public function attachment()
	{
		$app  = JFactory::getApplication();
		$file = base64_decode($app->input->getBase64('file'));

		if (!is_file($file))
		{
			throw new RuntimeException('File not found.');
		}

		$headers = array(
			'Pragma'                    => 'public',
			'Expires'                   => '0',
			'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
			'Content-Type'              => mime_content_type($file),
			'Content-Description'       => 'attachment; filename="' . basename($file) . '"',
			'Content-Transfer-Encoding' => 'binary',
			'Connection'                => 'close',
		);

		foreach ($headers as $name => $value)
		{
			$app->setHeader($name, $value);
		}

		$app->sendHeaders();
		echo file_get_contents($file);

		$app->close();
	}
}