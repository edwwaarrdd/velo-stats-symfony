<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Cross-origin access for the browser client.
 *
 * The policy is the same across every velo-stats backend: a configured list of
 * origins, any method, any header, no credentials. That is small enough to
 * express directly, and doing so keeps the origin list readable from the
 * environment, which the CORS bundles do not allow.
 */
final readonly class CorsListener
{
    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        #[Autowire('%app.cors_allowed_origins%')]
        private array $allowedOrigins,
    ) {
    }

    /**
     * A preflight request is answered here and never reaches a handler, which
     * is the whole point of preflight: it asks about the endpoint rather than
     * calling it.
     */
    #[AsEventListener(event: RequestEvent::class, priority: 250)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (! $request->isMethod('OPTIONS') || ! $request->headers->has('Origin')) {
            return;
        }

        $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
    }

    #[AsEventListener(event: ResponseEvent::class)]
    public function onResponse(ResponseEvent $event): void
    {
        $origin = $event->getRequest()->headers->get('Origin');

        if ($origin === null || ! in_array($origin, $this->allowedOrigins, true)) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('Access-Control-Allow-Origin', $origin);
        $headers->set('Access-Control-Allow-Methods', '*');
        $headers->set('Access-Control-Allow-Headers', '*');

        // Responses differ by origin, so a shared cache must not serve one
        // origin's response to another.
        $headers->set('Vary', 'Origin');
    }
}
