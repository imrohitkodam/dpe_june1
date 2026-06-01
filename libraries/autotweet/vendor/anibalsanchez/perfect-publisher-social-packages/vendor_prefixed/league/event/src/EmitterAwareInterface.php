<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\League\Event;

interface EmitterAwareInterface
{
    /**
     * Set the Emitter.
     *
     * @param EmitterInterface $emitter
     *
     * @return $this
     */
    public function setEmitter(EmitterInterface $emitter = null);

    /**
     * Get the Emitter.
     *
     * @return EmitterInterface
     */
    public function getEmitter();
}
