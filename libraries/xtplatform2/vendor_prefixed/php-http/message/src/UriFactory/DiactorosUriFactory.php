<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message\UriFactory;

use XTP_BUILD\Http\Message\UriFactory;
use XTP_BUILD\Psr\Http\Message\UriInterface;
use Zend\Diactoros\Uri;

/**
 * Creates Diactoros URI.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
final class DiactorosUriFactory implements UriFactory
{
    /**
     * {@inheritdoc}
     */
    public function createUri($uri)
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        } elseif (is_string($uri)) {
            return new Uri($uri);
        }

        throw new \InvalidArgumentException('URI must be a string or UriInterface');
    }
}
