<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    // protected $listen = [
    //     Registered::class => [
    //         SendEmailVerificationNotification::class,
    //     ],
    // ];
    // app/Providers/EventServiceProvider.php
protected $listen = [
    \App\Events\NewFormationEvent::class => [
        \App\Listeners\BroadcastNewFormation::class,
    ],
    \App\Events\NewCertificationEvent::class => [
        \App\Listeners\BroadcastNewCertification::class,
    ],
    \App\Events\NewInscriptionEvent::class => [
        \App\Listeners\BroadcastNewInscription::class,
    ],
    \App\Events\NewPlanningEvent::class => [
        \App\Listeners\BroadcastNewPlanning::class,
    ],
    \App\Events\NewLessonEvent::class => [
        \App\Listeners\BroadcastNewLesson::class,
    ],
];


    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
