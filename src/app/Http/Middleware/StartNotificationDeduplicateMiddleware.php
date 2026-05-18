<?php

namespace App\Http\Middleware;

use App\Services\DeduplicationRequest\DeduplicateRequestServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class StartNotificationDeduplicateMiddleware
{
    public function __construct(
        private readonly DeduplicateRequestServiceInterface $deduplicateRequestService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $receiverIds = $request->input('receiverIds');
        sort($receiverIds);

        $payload = [
            'text' => $request->input('text'),
            'channel' => $request->input('channel'),
            'receiverIds' => $receiverIds,
        ];

        if ($this->deduplicateRequestService->isDuplicate($payload)) {
            throw new TooManyRequestsHttpException(message: 'Duplicate request detected.');
        }

        return $next($request);
    }
}
