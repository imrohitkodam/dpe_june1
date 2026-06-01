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

namespace XTS_BUILD\Symfony\Component\Translation\Formatter;

use XTS_BUILD\Symfony\Component\Translation\MessageSelector;

/**
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 */
class MessageFormatter implements MessageFormatterInterface, ChoiceMessageFormatterInterface
{
    private $selector;

    /**
     * @param MessageSelector|null $selector The message selector for pluralization
     */
    public function __construct(MessageSelector $selector = null)
    {
        $this->selector = $selector ?: new MessageSelector();
    }

    /**
     * {@inheritdoc}
     */
    public function format($message, $locale, array $parameters = [])
    {
        return strtr($message, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function choiceFormat($message, $number, $locale, array $parameters = [])
    {
        $parameters = array_merge(['%count%' => $number], $parameters);

        return $this->format($this->selector->choose($message, (int) $number, $locale), $locale, $parameters);
    }
}
