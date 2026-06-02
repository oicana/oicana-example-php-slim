<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OA;

/**
 * OpenAPI specification metadata.
 * Endpoint documentation is in the Controllers directory.
 */
#[OA\Info(
    version: '1.0',
    title: 'Oicana example',
    description: 'PHP Slim example with Oicana'
)]
#[OA\ExternalDocumentation(
    description: 'General documentation for Oicana',
    url: 'https://oicana.com/docs/'
)]
#[OA\Tag(
    name: 'template',
    description: 'Template API endpoints. Find used templates at https://github.com/oicana/oicana-example-templates.'
)]
#[OA\Tag(
    name: 'certificates',
    description: 'Create certificates'
)]
#[OA\Tag(
    name: 'blob',
    description: 'Blob storage endpoints. Upload files (images, documents) to use as template inputs.'
)]
class OpenApi
{
}
