<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\Exception\InvalidCredentialsException;
use App\Security\Exception\TooManyLoginAttemptsException;
use App\Service\Exception\EmployeDejaExistantException;
use App\Service\Exception\LigneCommandeIntrouvableException;
use App\Service\Exception\ProduitDejaExistantException;
use App\Service\Exception\QuantiteRecueInvalideException;
use App\Service\Exception\StockInsuffisantException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Converts every exception raised under /api into a uniform JSON error body,
 * so controllers/services can just throw meaningful exceptions instead of
 * building error responses by hand. Feature branches extend the match arms
 * below with their own domain exceptions (see F1/F2/F3 commits) — this
 * version adds F3's commande exceptions on top of F1/F2's.
 */
final class ApiExceptionListener implements EventSubscriberInterface
{
    public function __construct(private readonly bool $debug)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();
        $headers = [];

        [$status, $body] = match (true) {
            $throwable instanceof ValidationFailedException => [422, [
                'error' => 'Données invalides.',
                'violations' => array_map(
                    static fn ($v) => ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()],
                    iterator_to_array($throwable->getViolations()),
                ),
            ]],
            $throwable instanceof InvalidCredentialsException => [401, ['error' => $throwable->getMessage()]],
            $throwable instanceof TooManyLoginAttemptsException => (function () use ($throwable, &$headers) {
                $headers['Retry-After'] = (string) $throwable->retryAfterSeconds;

                return [429, ['error' => $throwable->getMessage()]];
            })(),
            $throwable instanceof ProduitDejaExistantException => [409, [
                'error' => $throwable->getMessage(),
                'produit' => [
                    'idProduit' => $throwable->produit->getId(),
                    'nom' => $throwable->produit->getNom(),
                    'codeBarre' => $throwable->produit->getCodeBarre(),
                ],
            ]],
            $throwable instanceof StockInsuffisantException => [409, ['error' => $throwable->getMessage()]],
            $throwable instanceof EmployeDejaExistantException => [409, ['error' => $throwable->getMessage()]],
            $throwable instanceof LigneCommandeIntrouvableException => [404, ['error' => $throwable->getMessage()]],
            $throwable instanceof QuantiteRecueInvalideException => [422, ['error' => $throwable->getMessage()]],
            $throwable instanceof AuthenticationException => [401, ['error' => 'Authentification requise.']],
            $throwable instanceof AccessDeniedException => [403, ['error' => 'Accès refusé.']],
            $throwable instanceof HttpExceptionInterface => [
                $throwable->getStatusCode(),
                ['error' => $throwable->getMessage() ?: 'Erreur de requête.'],
            ],
            default => [500, ['error' => $this->debug ? $throwable->getMessage() : 'Erreur interne.']],
        };

        $event->setResponse(new JsonResponse($body, $status, $headers));
    }
}
