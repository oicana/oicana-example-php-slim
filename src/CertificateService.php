<?php

declare(strict_types=1);

namespace App;

use Oicana\CompilationMode;
use Oicana\ExportFormat;
use Oicana\OicanaException;

class CertificateService
{
    public function __construct(
        private TemplateService $templateService
    ) {
    }

    public function createCertificate(string $name): string
    {
        $template = $this->templateService->getTemplate('certificate');

        if ($template === null) {
            throw new \RuntimeException("Certificate template not found!");
        }

        $jsonInputs = [
            'certificate' => json_encode(['name' => $name])
        ];

        try {
            $start = hrtime(true);
            $result = $template->export(
                jsonInputs: $jsonInputs,
                exportFormat: ExportFormat::pdf(),
                mode: CompilationMode::Production
            );
            $elapsed = (hrtime(true) - $start) / 1_000_000;
            error_log(sprintf("Compiled 'certificate' to PDF in %.1fms", $elapsed));
            return $result;
        } catch (OicanaException $e) {
            error_log("Certificate template failed to compile: {$e->getMessage()}");
            throw $e;
        }
    }
}
