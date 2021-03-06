<?php

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Whoops\Exception\Inspector;

$container = $app->getContainer();
// DEBUGGER

if ($container->get('settings')['whoopsDebug']) {
    $app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware($app));
} else {
    $simplyErrorHandler = function (Exception $exception, Inspector $inspector, $run) use ($container) {
        $title = $inspector->getExceptionName();
        $code = $exception->getCode();
        $message = $inspector->getExceptionMessage();

        $container->get('logger')->error('Exception! '.$title.'('.$code.') -> '.$message);

        require 'Templates/exception.php';
        exit;
    };

    $app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware($app, [$simplyErrorHandler]));
}

// TRAILING SLASH REMOVER

$app->add(function (Request $request, Response $response, callable $next): ResponseInterface {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path !== '/' && substr($path, -1) === '/') {
        // permanently redirect paths with a trailing slash to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));

        if ($request->getMethod() === 'GET') {
            return $response->withRedirect((string)$uri, 301);
        }

        return $next($request->withUri($uri), $response);
    }

    $response = $next($request, $response);

    return $response;
});

// LOCALIZATION NEGOTIATOR
// https://github.com/tboronczyk/localization-middleware
// https://github.com/willdurand/Negotiation

// TRANSLATOR
// https://symfony.com/doc/current/components/translation.html


// CSRF PROTECTION
// https://github.com/slimphp/Slim-Csrf

// USER AUTHENTICATION

$app->add(function (Request $request, Response $response, callable $next) use ($container): ResponseInterface {
    /** @var \kissj\User\UserRegeneration $userRegeneration */
    $userRegeneration = $container->get(\kissj\User\UserRegeneration::class);
    $user = $userRegeneration->getCurrentUser();
    $request = $request->withAttribute('user', $user);

    $response = $next($request, $response);

    return $response;
});
