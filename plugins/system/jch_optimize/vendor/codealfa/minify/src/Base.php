<?php

/**
 * @package   codealfa/minify
 * @author    Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2020 Samuel Marshall
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace CodeAlfa\Minify;

defined('_JCH_EXEC') or die('Restricted access');

abstract class Base
{

	//regex for double quoted strings
	// language=RegExp
	const DOUBLE_QUOTE_STRING = '"(?>(?:\\\\.)?[^\\\\"]*+)+?(?:"|(?=$))';

	//regex for single quoted string
	// language=RegExp
	const SINGLE_QUOTE_STRING = "'(?>(?:\\\\.)?[^\\\\']*+)+?(?:'|(?=$))";

	//regex for backtick quoted string
	//language=RegExp
	const BACK_TICK_STRING = '`(?>(?:\\\\.)?[^\\\\`]*+)+?(?:`|(?=$))';

	//regex for block comments
	// language=RegExp
	const BLOCK_COMMENT = '/\*(?>[^*]++|\*(?!/))*+\*/';

	//regex for line comments
	// language=RegExp
	const LINE_COMMENT = '//[^\r\n]*+';

	//regex for HTML comments
	// language=RegExp
	const HTML_COMMENT = '(?:(?:<!--|(?<=[\s/^])-->)[^\r\n]*+)';

	//Regex for HTML attributes
	// language=RegExp
	const HTML_ATTRIBUTE = '[^\s/"\'=<>]*+(?:\s*=(?>\s*+"[^"]*+"|\s*+\'[^\']*+\'|[^\s>]*+[\s>]))?';

	//Regex for HTML attribute values
	// language=RegExp
	const ATTRIBUTE_VALUE = '(?>(?<=")[^"]*+|(?<=\')[^\']*+|(?<==)[^\s*+>]*+)';

	// language=RegExp
	const URI = '(?<=url)\(\s*+(?:"[^"]*+"|\'[^\']*+\'|[^)]*+)\s*+\)';

	public $_debug = false;
	public $_regexNum = -1;
	public $_limit = 10;
	public $_printCode = true;

	protected function __construct($options)
	{
		foreach ($options as $key => $value)
		{
			$this->{'_' . $key} = $value;
		}

		if (!defined('CODEALFA_MINIFY_CONFIGURED'))
		{
			ini_set('pcre.backtrack_limit', 1000000);
			ini_set('pcre.recursion_limit', 100000);
			ini_set('pcre.jit', 0);

			define('CODEALFA_MINIFY_CONFIGURED', 1);
		}
	}

	/**
	 *
	 * @param   string   $regex
	 * @param   string   $code
	 * @param   integer  $regexNum
	 *
	 * @return boolean|void
	 */
	public function _debug($regex, $code, $regexNum = 0)
	{
		if (!$this->_debug) return false;

		/** @var float $pstamp */
		static $pstamp = 0;

		if ($pstamp === 0)
		{
			$pstamp = microtime(true);

			return;
		}

		$nstamp = microtime(true);
		$time   = $nstamp - $pstamp;

		if ($time > $this->_limit)
		{
			print 'num=' . $regexNum . "\n";
			print 'time=' . $time . "\n\n";

			if ($this->_printCode)
			{
				print $regex . "\n";
				print $code . "\n\n";
			}
		}


		$pstamp = $nstamp;
	}

	/**
	 *
	 * @staticvar bool $tm
	 *
	 * @param   string    $regex
	 * @param   string    $replacement
	 * @param   string    $code
	 * @param   mixed     $regex_num
	 * @param   callable  $callback
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _replace($regex, $replacement, $code, $regex_num, $callback = null)
	{
		static $tm = false;

		if ($tm === false)
		{
			$this->_debug('', '');
			$tm = true;
		}

		if (empty($callback))
		{
			$op_code = preg_replace($regex, $replacement, $code);
		}
		else
		{
			$op_code = preg_replace_callback($regex, $callback, $code);
		}

		$this->_debug($regex, $code, $regex_num);

		$error = array_flip(array_filter(get_defined_constants(true)['pcre'], function ($value) {
			return substr($value, -6) === '_ERROR';
		}, ARRAY_FILTER_USE_KEY))[preg_last_error()];

		if (preg_last_error() != PREG_NO_ERROR)
		{
			throw new \Exception($error);
		}

		return $op_code;
	}

}
