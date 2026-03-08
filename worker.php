<?php

declare(strict_types=1);

use App\BlobService;
use App\CertificateService;
use App\TemplateService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

require __DIR__ . '/vendor/autoload.php';

error_log("Starting Oicana example service...");

$templateService = new TemplateService();
$blobService = new BlobService();
$certificateService = new CertificateService($templateService);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

(require __DIR__ . '/src/Routes/templates.php')($app, $templateService, $blobService);
(require __DIR__ . '/src/Routes/blobs.php')($app, $blobService);
(require __DIR__ . '/src/Routes/certificates.php')($app, $certificateService);

// OpenAPI JSON endpoint
$app->get('/openapi.json', function (Request $request, Response $response) {
    $openapi = (new \OpenApi\Generator())->generate([__DIR__ . '/src']);
    $response->getBody()->write($openapi->toJson());
    return $response->withHeader('Content-Type', 'application/json');
});

// Swagger UI at root
$app->get('/', function (Request $request, Response $response) {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Oicana example - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; padding:0; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
    window.onload = function() {
        window.ui = SwaggerUIBundle({
            url: "/openapi.json",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        });
    };
    </script>
</body>
</html>
HTML;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$psr17Factory = new Psr17Factory();
$psr7Worker = new PSR7Worker(
    Worker::create(),
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

while ($request = $psr7Worker->waitRequest()) {
    try {
        $response = $app->handle($request);
        $psr7Worker->respond($response);
    } catch (\Throwable $e) {
        $psr7Worker->getWorker()->error((string) $e);
    }
}
