<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Message\Encoding;

/**
 * Transform a regular stream into a chunked one.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class ChunkStream extends FilteredStream
{
    /**
     * {@inheritdoc}
     */
    protected function readFilter()
    {
        return 'chunk';
    }

    /**
     * {@inheritdoc}
     */
    protected function writeFilter()
    {
        return 'dechunk';
    }

    /**
     * {@inheritdoc}
     */
    protected function fill()
    {
        parent::fill();

        if ($this->stream->eof()) {
            $this->buffer .= "0\r\n\r\n";
        }
    }
}
