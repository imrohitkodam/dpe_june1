<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

// Register chunk filter if not found
if (!array_key_exists('chunk', stream_get_filters())) {
    stream_filter_register('chunk', 'XTS_BUILD\Http\Message\Encoding\Filter\Chunk');
}
