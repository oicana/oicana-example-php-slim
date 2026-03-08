<?php

declare(strict_types=1);

use App\BlobService;
use App\Controllers\TemplatesController;
use App\TemplateService;
use Slim\App;

return function (App $app, TemplateService $templateService, BlobService $blobService): void {
    $controller = new TemplatesController($templateService, $blobService);

    $app->get('/templates', [$controller, 'list']);
    $app->post('/templates/{template_id}/compile', [$controller, 'compile']);
    $app->post('/templates/{template_id}/preview', [$controller, 'preview']);
    $app->post('/templates/{template_id}/reset', [$controller, 'reset']);
    $app->get('/templates/{template_id}', [$controller, 'download']);
};
