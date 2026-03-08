<?php

declare(strict_types=1);

namespace App\Controllers;

use App\BlobService;
use App\TemplateService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TemplatesController
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly BlobService $blobService
    ) {
    }

    #[OA\Get(
        path: '/templates',
        tags: ['template'],
        summary: 'List all available templates',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of template IDs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            )
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode($this->templateService->getTemplateIds()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: '/templates/{template_id}/compile',
        tags: ['template'],
        summary: 'Compile template to PDF',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'jsonInputs' => [
                        ['key' => 'data', 'value' => [
                            'description' => 'from sample data',
                            'rows' => [
                                ['name' => 'Frank', 'one' => 'first', 'two' => 'second', 'three' => 'third']
                            ]
                        ]]
                    ],
                    'blobInputs' => [
                        ['key' => 'logo', 'blobId' => '00000000-0000-0000-0000-000000000000']
                    ]
                ],
                properties: [
                    new OA\Property(
                        property: 'jsonInputs',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'value', type: 'object')
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(
                        property: 'blobInputs',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'blobId', type: 'string', format: 'uuid', example: '00000000-0000-0000-0000-000000000000')
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'table')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PDF file',
                content: new OA\MediaType(
                    mediaType: 'application/pdf',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Template not found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Compilation failed',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function compile(Request $request, Response $response, array $args): Response
    {
        $templateId = $args['template_id'];

        try {
            $pdfBytes = $this->templateService->compileTemplate($templateId, $request->getParsedBody(), $this->blobService);

            $response->getBody()->write($pdfBytes);
            return $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', "attachment; filename=\"{$templateId}.pdf\"");
        } catch (\InvalidArgumentException $e) {
            $status = $this->templateService->getTemplate($templateId) === null ? 404 : 400;
            $response->getBody()->write(json_encode(['detail' => $e->getMessage()]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'detail' => "Template '{$templateId}' failed to compile with given inputs: {$e->getMessage()}"
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/templates/{template_id}/preview',
        tags: ['template'],
        summary: 'Preview template as PNG',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'jsonInputs' => [
                        ['key' => 'data', 'value' => [
                            'description' => 'from sample data',
                            'rows' => [
                                ['name' => 'Frank', 'one' => 'first', 'two' => 'second', 'three' => 'third']
                            ]
                        ]]
                    ],
                    'blobInputs' => [
                        ['key' => 'logo', 'blobId' => '00000000-0000-0000-0000-000000000000']
                    ]
                ],
                properties: [
                    new OA\Property(
                        property: 'jsonInputs',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'value', type: 'object')
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(
                        property: 'blobInputs',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'blobId', type: 'string', format: 'uuid', example: '00000000-0000-0000-0000-000000000000')
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'table')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PNG image',
                content: new OA\MediaType(
                    mediaType: 'image/png',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Template not found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Compilation failed',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function preview(Request $request, Response $response, array $args): Response
    {
        $templateId = $args['template_id'];

        try {
            $pngBytes = $this->templateService->previewTemplate($templateId, $request->getParsedBody(), $this->blobService);

            $response->getBody()->write($pngBytes);
            return $response
                ->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Disposition', "inline; filename=\"{$templateId}.png\"");
        } catch (\InvalidArgumentException $e) {
            $status = $this->templateService->getTemplate($templateId) === null ? 404 : 400;
            $response->getBody()->write(json_encode(['detail' => $e->getMessage()]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'detail' => "Template '{$templateId}' failed to compile: {$e->getMessage()}"
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/templates/{template_id}/reset',
        tags: ['template'],
        summary: 'Reset template cache',
        parameters: [
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Template cache reset'),
            new OA\Response(
                response: 404,
                description: 'Template not found in cache',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function reset(Request $request, Response $response, array $args): Response
    {
        $templateId = $args['template_id'];

        if ($this->templateService->resetTemplate($templateId)) {
            return $response->withStatus(204);
        }

        $response->getBody()->write(json_encode(['detail' => "Template '{$templateId}' not found in cache"]));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: '/templates/{template_id}',
        tags: ['template'],
        summary: 'Download template ZIP file',
        parameters: [
            new OA\Parameter(
                name: 'template_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template ZIP file',
                content: new OA\MediaType(
                    mediaType: 'application/zip',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Template not found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'detail', type: 'string')]
                )
            )
        ]
    )]
    public function download(Request $request, Response $response, array $args): Response
    {
        $templateId = $args['template_id'];
        $templatePath = $this->templateService->getTemplatePath($templateId);

        if ($templatePath === null) {
            $response->getBody()->write(json_encode(['detail' => 'Template not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $fileContent = file_get_contents($templatePath);
        if ($fileContent === false) {
            $response->getBody()->write(json_encode(['detail' => 'Failed to read template file']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write($fileContent);
        return $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$templateId}.zip\"");
    }

}
