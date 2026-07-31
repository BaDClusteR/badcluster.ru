<?php

declare(strict_types=1);

namespace BC\Controller\System;

use BC\Core\Response\HtmlResponse;
use Runway\Controller\IExceptionController;
use Runway\Exception\FatalErrorException;
use Runway\Logger\ILogger;
use Runway\Request\IRequest;
use Runway\Request\Response;
use Runway\Singleton\IKernel;
use Throwable;

/**
 * Разделяет вывод непойманных исключений: для API-запросов отдаёт JSON
 * (внутренний декоратор API platform), для обычных веб-запросов — HTML-страницу.
 */
class ExceptionController implements IExceptionController {
    public function __construct(
        protected IExceptionController $apiExceptionController,
        protected IRequest $request,
        protected IKernel $kernel,
        protected ILogger $logger
    ) {
    }

    public function run(Throwable $exception): Response {
        if ($this->isApiRequest()) {
            return $this->apiExceptionController->run($exception);
        }

        $this->logException($exception);

        return new HtmlResponse(
            500,
            $this->kernel->isDebugMode()
                ? $this->renderDebugPage($exception)
                : $this->renderErrorPage()
        );
    }

    protected function isApiRequest(): bool {
        $path = $this->request->getPath();

        return $path === '/admin/api' || str_starts_with($path, '/admin/api/');
    }

    protected function logException(Throwable $exception): void {
        $this->logger->error(
            "Uncaught exception: {$exception->getMessage()}",
            [
                'exceptionType'  => get_debug_type($exception),
                'file'           => $this->getFile($exception),
                'line'           => $this->getLine($exception),
                'exceptionTrace' => $exception->getTrace(),
            ]
        );
    }

    protected function renderErrorPage(): string {
        $requestId = $this->escape($this->request->getRequestId());

        return $this->wrapInLayout(
            'Упс!',
            <<<HTML
            <h1>Упс!</h1>
            <p>Что-то пошло не так на сервере. Попробуйте обновить страницу или зайти позже.</p>
            <p>Если ошибка повторяется, пишите на <a href="mailto:admin@badcluster.ru">admin@badcluster.ru</a> — постараюсь пофиксить как можно скорее.</p>
            <p class="secondary">ID запроса: {$requestId}</p>
            HTML
        );
    }

    protected function renderDebugPage(Throwable $exception): string {
        $blocks = [];

        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            $blocks[] = $this->renderExceptionBlock($e, $e !== $exception);
        }

        return $this->wrapInLayout(
            'Произошла ошибка',
            implode('', $blocks)
        );
    }

    protected function renderExceptionBlock(Throwable $exception, bool $isPrevious): string {
        $type = $this->escape(get_debug_type($exception));
        $message = $this->escape($exception->getMessage());
        $location = $this->escape("{$this->getFile($exception)}:{$this->getLine($exception)}");
        $trace = $this->escape($exception->getTraceAsString());
        $heading = $isPrevious ? '<p class="secondary">Вызвано исключением:</p>' : '';

        return <<<HTML
        {$heading}
        <h1>{$type}</h1>
        <p class="message">{$message}</p>
        <p class="secondary">{$location}</p>
        <pre>{$trace}</pre>
        HTML;
    }

    protected function wrapInLayout(string $title, string $content): string {
        $title = $this->escape($title);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex">
            <link rel="icon" href="/favicon-error.svg" type="image/svg+xml">
            <title>{$title}</title>
            <style>
                :root { color-scheme: light dark; }
                body {
                    font-family: system-ui, sans-serif;
                    line-height: 1.5;
                    max-width: 60rem;
                    margin: 0 auto;
                    padding: 3rem 1.5rem;
                }
                h1 { margin: 0 0 .5rem; font-size: 2rem; line-height: 1.35; }
                .message { font-size: 1.125rem; margin: 0 0 .25rem; }
                .secondary { color: light-dark(#666, #999); margin: 0 0 1rem; font-size: .9em; }
                a {
                    color: light-dark(#26914E, #4ADE80);
                    text-decoration: none;

                    &:hover,
                    &:focus-visible,
                    &:active {
                        color: light-dark(#166534, #86EFAC);
                        text-decoration: underline;
                    }
                }
                pre {
                    background: light-dark(#f5f5f5, #222);
                    padding: 1rem;
                    border-radius: .5rem;
                    overflow-x: auto;
                    font-size: .8125rem;
                }
            </style>
        </head>
        <body>{$content}</body>
        </html>
        HTML;
    }

    protected function getFile(Throwable $exception): string {
        return $exception instanceof FatalErrorException
            ? $exception->getErrFile()
            : $exception->getFile();
    }

    protected function getLine(Throwable $exception): int {
        return $exception instanceof FatalErrorException
            ? $exception->getErrLine()
            : $exception->getLine();
    }

    protected function escape(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}
