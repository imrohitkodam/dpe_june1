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
 * PhpFileDumper generates PHP files from a message catalogue.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 */
class PhpFileDumper extends FileDumper
{
    /**
     * {@inheritdoc}
     */
    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = [])
    {
        return "<?php\n\nreturn ".var_export($messages->all($domain), true).";\n";
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension()
    {
        return 'php';
    }
}
