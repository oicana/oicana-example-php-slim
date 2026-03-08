<?php

declare(strict_types=1);

namespace App\Controllers;

use App\BlobService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BlobsController
{
    public function __construct(
        private readonly BlobService $blobService
    ) {
    }

    #[OA\Post(
        path: '/blobs',
        tags: ['blob'],
        summary: 'Upload a blob (image, file, etc.)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Blob uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'No file uploaded',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to save file',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function upload(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['detail' => 'No file uploaded']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $file = $uploadedFiles['file'];

        try {
            $fileData = (string) $file->getStream();
            $blobId = $this->blobService->uploadBlob($fileData);

            $response->getBody()->write(json_encode(['id' => $blobId->toString()]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Failed to upload blob: {$e->getMessage()}");
            $response->getBody()->write(json_encode(['detail' => 'Failed to save file']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
