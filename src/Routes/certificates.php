<?php

declare(strict_types=1);

use App\CertificateService;
use App\Controllers\CertificatesController;
use Slim\App;

return function (App $app, CertificateService $certificateService): void {
    $controller = new CertificatesController($certificateService);

    $app->post('/certificates', [$controller, 'create']);
};
