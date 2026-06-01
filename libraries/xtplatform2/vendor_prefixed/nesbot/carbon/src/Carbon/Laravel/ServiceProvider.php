<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Carbon\Laravel;

use XTP_BUILD\Carbon\Carbon;
use XTP_BUILD\Illuminate\Events\Dispatcher;
use XTP_BUILD\Illuminate\Events\EventDispatcher;
use XTP_BUILD\Illuminate\Translation\Translator as IlluminateTranslator;
use XTP_BUILD\Symfony\Component\Translation\Translator;

class ServiceProvider extends \XTP_BUILD\Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $service = $this;
        $events = $this->app['events'];
        if ($events instanceof EventDispatcher || $events instanceof Dispatcher) {
            $events->listen(class_exists('XTP_BUILD\Illuminate\Foundation\Events\LocaleUpdated') ? 'XTP_BUILD\Illuminate\Foundation\Events\LocaleUpdated' : 'locale.changed', function () use ($service) {
                $service->updateLocale();
            });
            $service->updateLocale();
        }
    }

    public function updateLocale()
    {
        $translator = $this->app['translator'];
        if ($translator instanceof Translator || $translator instanceof IlluminateTranslator) {
            Carbon::setLocale($translator->getLocale());
        }
    }

    public function register()
    {
        // Needed for Laravel < 5.3 compatibility
    }
}
