<?php

/**
 * @package   codealfa/regextokenizer
 * @author    Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2020 Samuel Marshall
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace CodeAlfa\RegexTokenizer;

defined('_JCH_EXEC') or die('Restricted access');

trait Css
{
	use Base;

	//language=RegExp
	public static function CSS_IDENT()
	{
		return '(?:\\\\.|[a-z0-9_-]++\s++)';
	}

	//language=RegExp
	public static function CSS_URL_VALUE_UNQUOTED()
	{
		return '(?<=url\()(?>(?:\\\\.)?[^\\\\()\s\'"]*+)++';
	}

	//language=RegExp
	public static function CSS_URL_VALUE()
	{
		return '(?:' . self::STRING_VALUE() . '|' . self::CSS_URL_VALUE_UNQUOTED() . ')';
	}

	//language=RegExp
	public static function CSS_URL_CP( $bCV = false )
	{
		$sCssUrl = 'url\([\'"]?<<' . self::CSS_URL_VALUE() . '>>[\'"]?\)';

		return self::prepare( $sCssUrl, $bCV );
	}

}

