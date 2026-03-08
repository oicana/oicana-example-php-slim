<?php

declare(strict_types=1);

namespace App\Controllers;

use App\CertificateService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CertificatesController
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {
    }

    #[OA\Post(
        path: '/certificates',
        tags: ['certificates'],
        summary: 'Create a certificate PDF',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Name to appear on the certificate', example: 'Jane Doe')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate PDF',
                content: new OA\MediaType(
                    mediaType: 'application/pdf',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request or compilation failed',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $name = $body['name'] ?? null;

        if ($name === null || $name === '') {
            $response->getBody()->write(json_encode(['detail' => 'Name is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdfBytes = $this->certificateService->createCertificate($name);

            $response->getBody()->write($pdfBytes);
            return $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="certificate.pdf"');
        } catch (\Exception $e) {
            error_log("Failed to create certificate: {$e->getMessage()}");
            $response->getBody()->write(json_encode(['detail' => "Failed to compile certificate: {$e->getMessage()}"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
