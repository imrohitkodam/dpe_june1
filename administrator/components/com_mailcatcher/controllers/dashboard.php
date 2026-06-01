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

class MailcatcherControllerDashboard extends JControllerLegacy
{
	protected function loadChartByToday(&$chartData, $sentMails)
	{
		if (!empty($sentMails))
		{
			foreach ($sentMails as $sentMail)
			{
				$label = JHtml::_('date', $sentMail->created_date, 'H:00');

				if (!in_array($label, $chartData['labels']))
				{
					$chartData['labels'][]              = $label;
					$chartData['datasets'][0]['data'][] = 0;
				}
			}

			sort($chartData['labels']);
		}

		return $this->loadChartByLabelFormat($chartData, $sentMails, 'H:00');
	}

	protected function loadChartByLabelFormat(&$chartData, $sentMails, $formatLabel = null)
	{
		if (!empty($sentMails))
		{
			foreach ($sentMails as $sentMail)
			{
				$label = JHtml::_('date', $sentMail->created_date, $formatLabel);
				$key   = array_search($label, $chartData['labels']);

				if (isset($chartData['datasets'][0]['data'][$key]))
				{
					$chartData['datasets'][0]['data'][$key]++;
				}
			}
		}
	}

	public function loadChartData()
	{
		try
		{
			if (!JSession::checkToken())
			{
				throw new RuntimeException(JText::_('JINVALID_TOKEN'));
			}

			/** @var $model \MailcatcherModelMails */
			$model  = $this->getModel('Mails', 'MailcatcherModel', array('ignore_request' => true));
			$today  = JFactory::getDate();
			$jDate  = JFactory::getDate();
			$period = (int) $this->input->getInt('period', 0);

			switch ($period)
			{
				case 1:
					$fromDate = $jDate->format('Y-m') . '-01';
					$toDate   = $today->format('Y-m-d');
					break;

				case 2:
				case 3:
				case 4:
					if ($period == 2)
					{
						$sub = 0;
					}
					elseif ($period == 3)
					{
						$sub = 2;
					}
					else
					{
						$sub = 5;
					}

					$subDate = JFactory::getDate($today->format('Y') . '-' . $today->format('m') . '-01');
					$subDate->sub(new DateInterval('P1M'));
					$toDate = $subDate->format('Y-m-t');

					if ($sub > 0)
					{
						$subDate->sub(new DateInterval('P' . $sub . 'M'));
					}

					$fromDate = $subDate->format('Y-m') . '-01';

					break;

				case 5:
					$fromDate = $jDate->format('Y') . '-01-01';
					$toDate   = $today->format('Y-m-d');
					break;

				default:
					$fromDate = $today->format('Y-m-d');
					$toDate   = $fromDate;
					break;
			}

			$model->setState('filter.from_date', $fromDate);
			$model->setState('filter.to_date', $toDate);
			$model->setState('list.start', 0);
			$model->setState('list.limit', 0);
			$model->setState('list.ordering', 'a.created_date');
			$model->setState('list.direction', 'asc');
			$sentMails = $model->getItems();
			$labels    = array(
				0 => JText::_('COM_MAILCATCHER_TODAY'),
				1 => JText::_('COM_MAILCATCHER_THIS_MONTH'),
				2 => JText::_('COM_MAILCATCHER_LAST_MONTH'),
				3 => JText::_('COM_MAILCATCHER_LAST_3_MONTHS'),
				4 => JText::_('COM_MAILCATCHER_LAST_6_MONTHS'),
				5 => JText::_('COM_MAILCATCHER_THIS_YEAR'),
			);
			$chartData = array(
				'labels'   => array(),
				'datasets' => array(
					array(
						'label'           => $labels[$period] . ' (' . sprintf('%02d', count($sentMails)) . ')',
						'data'            => array(),
						'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
						'borderColor'     => 'rgba(255,99,132,1)',
						'fill'            => true,
						'borderWidth'     => 1,
					),
				),
			);

			if ($period === 0)
			{
				$this->loadChartByToday($chartData, $sentMails);
			}
			else
			{
				$fromJDate = JFactory::getDate($fromDate);
				$toJDate   = JFactory::getDate($toDate);
				$diff      = $fromJDate->diff($toJDate);

				while ((int) $diff->format('%R%a') >= 0)
				{
					$chartData['labels'][]              = JHtml::_('date', $fromJDate->format('Y-m-d'), 'd F Y');
					$chartData['datasets'][0]['data'][] = 0;
					$fromJDate->add(new DateInterval('P1D'));
					$diff = $fromJDate->diff($toJDate);
				}

				$this->loadChartByLabelFormat($chartData, $sentMails, 'd F Y');
			}

			$response = array(
				'chartData' => $chartData,
			);
		}
		catch (RuntimeException $e)
		{
			$response = $e;
		}

		echo new JResponseJson($response);

		JFactory::getApplication()->close();
	}
}