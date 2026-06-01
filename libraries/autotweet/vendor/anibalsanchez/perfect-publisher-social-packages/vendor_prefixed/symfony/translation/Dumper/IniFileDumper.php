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
 * IniFileDumper generates an ini formatted string representation of a message catalogue.
 *
 * @author Stealth35
 */
class IniFileDumper extends FileDumper
{
    /**
     * {@inheritdoc}
     */
    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = [])
    {
        $output = '';

        foreach ($messages->all($domain) as $source => $target) {
            $escapeTarget = str_replace('"', '\"', $target);
            $output .= $source.'="'.$escapeTarget."\"\n";
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension()
    {
        return 'ini';
    }
}
