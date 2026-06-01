<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\AdminallowlistModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:ipallow:remove
 *
 */
class IpAllowRemove extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:ipallow:remove';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$id = (int) $this->cliInput->getArgument('id') ?? 0;

		/** @var AdminallowlistModel $model */
		$model = $this->getMVCFactory()->createModel('Adminallowlist', 'Administrator');
		$table = $model->getTable();

		if (!$table->load($id))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_REMOVE_ERR_NOTFOUND', $id));

			return 2;
		}

		if (!$table->delete($id))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_REMOVE_ERR_FAILED', $id, $table->getError()));

			return 3;
		}

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_COMMON_REMOVE_LBL_SUCCESS', $table->getId()));

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addArgument('id', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_COMMON_OPT_ID'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_IPALLOW_REMOVE_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_IPALLOW_REMOVE_HELP'));
	}
}
