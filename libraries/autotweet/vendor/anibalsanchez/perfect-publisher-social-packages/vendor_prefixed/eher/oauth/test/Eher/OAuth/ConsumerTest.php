<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Eher\OAuth;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testConsumer()
    {
        $consumer = null;

        $consumer = new Consumer("ConsumerKey", "ConsumerSecret");

        $this->assertEquals(
            'Consumer[key=ConsumerKey,secret=ConsumerSecret]',
            (string) $consumer
        );
    }
}
