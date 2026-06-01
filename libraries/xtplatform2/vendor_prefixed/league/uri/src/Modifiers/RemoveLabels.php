<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package   League.uri
 * @author    Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.1.0
 * @link      https://github.com/thephpleague/uri/
 */
namespace XTP_BUILD\League\Uri\Modifiers;

use XTP_BUILD\League\Uri\Components\Host;
use XTP_BUILD\League\Uri\Modifiers\Filters\Keys;

/**
 * Remove labels from the URI host
 *
 * @package League.uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   4.0.0
 */
class RemoveLabels extends AbstractHostModifier
{
    use Keys;

    /**
     * New instance
     *
     * @param int[] $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * Modify a URI part
     *
     * @param string $str the URI part string representation
     *
     * @return string the modified URI part string representation
     */
    protected function modify($str)
    {
        return (string) (new Host($str))->without($this->keys);
    }
}
