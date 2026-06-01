<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTS_BUILD\Symfony\Component\Translation\Dumper;

use XTS_BUILD\Symfony\Component\Translation\MessageCatalogue;

/**
 * DumperInterface is the interface implemented by all translation dumpers.
 * There is no common option.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 */
interface DumperInterface
{
    /**
     * Dumps the message catalogue.
     *
     * @param MessageCatalogue $messages The message catalogue
     * @param array            $options  Options that are used by the dumper
     */
    public function dump(MessageCatalogue $messages, $options = []);
}
