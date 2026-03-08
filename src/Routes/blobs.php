<?php

declare(strict_types=1);

use App\BlobService;
use App\Controllers\BlobsController;
use Slim\App;

return function (App $app, BlobService $blobService): void {
    $controller = new BlobsController($blobService);

    $app->post('/blobs', [$controller, 'upload']);
};
