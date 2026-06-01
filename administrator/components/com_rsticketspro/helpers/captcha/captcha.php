<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2020 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproCaptcha
{
	protected $chars = 4;
	protected $code = null;

	protected function getFont()
	{
		return __DIR__ . '/monofont.ttf';
	}

	protected function generateCode()
	{
		$possible = 'aAbBcCdDeEfFgGhHjJkKLmMnNpPqQrRstTvVwWxXyYzZ2346789';
		$count = strlen($possible) - 1;
		$this->code = '';

		for ($i = 0; $i < $this->chars; $i++)
		{
			$this->code .= substr($possible, mt_rand(0, $count), 1);
		}

		JFactory::getSession()->set('com_rsticketspro.captcha', $this->code);

		return $this->code;
	}

	public function check($code)
	{
		$validCode = (string) JFactory::getSession()->get('com_rsticketspro.captcha');
		if (!RSTicketsProHelper::getConfig('captcha_case_sensitive'))
		{
			$validCode = strtolower($validCode);
			$code = strtolower($code);
		}

		JFactory::getSession()->clear('com_rsticketspro.captcha');

		return $validCode === $code;
	}

	public function setLength($chars)
	{
		$this->chars = (int) $chars;
	}

	public function getImage()
	{
		try
		{
			if (!function_exists('imagecreate'))
			{
				throw new Exception('imagecreate() not available.');
			}
			if (!function_exists('imagettfbbox'))
			{
				throw new Exception('imagettfbbox() not available.');
			}

			$code = $this->generateCode();
			$font = $this->getFont();

			$font_size = 32;

			$box = imagettfbbox($font_size, 0, $font, 'M');

			$char_w = abs($box[4] - $box[0]);
			$char_h = abs($box[5] - $box[1]);

			$width = (int) ($char_w * $this->chars * 1.3);
			$height = (int) ($char_h * 2);

			// Create the image
			$image = imagecreate($width, $height);

			// Get a random text color
			$r = mt_rand(0, 255);
			$g = mt_rand(0, 255);
			$b = mt_rand(0, 255);

			// Fill the background with a complementary color
			imagecolorallocate($image, ($r < 128) ? 255 : 0, ($g < 128) ? 255 : 0, ($b < 128) ? 255 : 0);

			// Allocate text color
			$color = imagecolorallocate($image, $r, $g, $b);

			// Get a random angle
			$angle = mt_rand( 0, 5 ) * (mt_rand(0, 1) == 1 ? -1 : 1);

			// Get the box size
			$text_box_size = imagettfbbox($font_size, $angle, $font, $code);

			// Calculate position
			$x = floor(($width - $text_box_size[4]) / 2);
			$y = floor(($height - $text_box_size[5]) / 2);

			// Set a shadow
			$shadow_color = imagecolorallocate($image, floor($r / 2), floor($g / 2), floor($b / 2));
			imagettftext($image, $font_size, $angle, $x - 1, $y + 3, $shadow_color, $font, $code);

			// Write our text
			imagettftext($image, $font_size, $angle, $x, $y, $color, $font, $code);

			imagejpeg($image);
			imagedestroy($image);
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}
	}
}
