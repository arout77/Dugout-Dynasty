<?php

namespace Core\Events;

use Core\Container;

/**
 * The central event dispatcher for the application.
 * It manages a map of events to their listeners and is responsible for
 * calling the correct listeners when an event is fired.
 */
class EventDispatcher
{
    /**
     * @param Container $container The service container, used to resolve listeners.
     * @param array $listeners A map of event classes to an array of listener classes.
     */
    public function __construct(
        protected Container $container,
        protected array $listeners = []
    ) {
    }

    /**
     * Dispatches an event to all of its registered listeners.
     *
     * @param Event $event The event object to dispatch.
     * @return void
     */
    public function dispatch( Event $event ): void
    {
        $eventName = get_class( $event );

        if ( isset( $this->listeners[$eventName] ) ) {
            foreach ( $this->listeners[$eventName] as $listenerClass ) {
                // Use the container to create an instance of the listener,
                // which automatically injects its dependencies.
                $listener = $this->container->resolve( $listenerClass );

                if ( $listener instanceof ListenerInterface ) {
                    $listener->handle( $event );
                }
            }
        }
    }
}
