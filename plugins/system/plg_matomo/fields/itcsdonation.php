<?php
/*
# Joomla.Plugin - itcs donation field
# ------------------------------------------------------------------------
# Author    it-conserv.de
# Copyright (C) 2019 it-conserv.de All Rights Reserved.
# License - GNU/GPLv3 <http://www.gnu.org/licenses/gpl-3.0.de.html>
# Websites: it-conserv.de
# ------------------------------------------------------------------------
*/ 

// No direct access
defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * Form Field class for Kubik-Rubik Joomla! Extensions.
 * Provides a donation code check.
 * credits Viktor Vogel
 */
class JFormFieldItcsDonation extends JFormField
{
	protected $type = 'itcsdonation';

	protected function getInput()
	{
		$html = '<a class="btn btn-success" href="https://www.paypal.me/peerluks/5EUR" target="_blank"><span class="icon-smiley-2 icon-white" aria-hidden="true"></span> 5 €</a> <a class="btn btn-success" href="https://www.paypal.me/peerluks/10EUR" target="_blank"><span class="icon-thumbs-up icon-white" aria-hidden="true"></span> 10 €</a> <a class="btn btn-success" href="https://www.paypal.me/peerluks/15EUR" target="_blank"><span class="icon-heart-2 icon-white" aria-hidden="true"></span> 15 €</a> <a class="btn btn-success" href="https://www.paypal.me/peerluks/" target="_blank"><span class="icon-star icon-white" aria-hidden="true"></span> # €</a>';
		return $html;
	}

	protected function getLabel()
	{
		return JText::_('ITCSDONATION'); //ITCSDONATION="Projekt unterstützen?"
	}
}
