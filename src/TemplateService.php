<?php

declare(strict_types=1);

namespace App;

use Oicana\CompilationMode;
use Oicana\ExportFormat;
use Oicana\Inputs\BlobInput;
use Oicana\Template;
use Ramsey\Uuid\Uuid;

class TemplateService
{
    private const TEMPLATES = [
        ['accessibility', '0.1.0'],
        ['certificate', '0.1.0'],
        ['dependency', '0.1.0'],
        ['fonts', '0.1.0'],
        ['invoice', '0.1.0'],
        ['invoice_zugferd', '0.1.0'],
        ['minimal', '0.1.0'],
        ['table', '0.1.0'],
        ['multi_input', '0.1.0'],
    ];

    /** @var array<string, Template> */
    private array $cache = [];

    public function __construct()
    {
        error_log("Warming up templates...");
        $this->warmUp();
    }

    public function warmUp(): void
    {
        foreach (self::TEMPLATES as [$templateId, $version]) {
            $templateFile = __DIR__ . "/../templates/{$templateId}-{$version}.zip";

            if (!file_exists($templateFile)) {
                error_log("Template file not found: {$templateFile}");
                continue;
            }

            try {
                $templateBytes = file_get_contents($templateFile);
                if ($templateBytes === false) {
                    throw new \RuntimeException("Failed to read template file");
                }

                $start = hrtime(true);
                $template = new Template($templateBytes, mode: CompilationMode::Development);
                $elapsed = (hrtime(true) - $start) / 1_000_000;
                $this->cache[$templateId] = $template;
                error_log(sprintf("Warmed-up %s v%s in %.1fms", $templateId, $version, $elapsed));
            } catch (\Exception $e) {
                error_log("Failed to warm up template {$templateId} v{$version}: {$e->getMessage()}");
            }
        }
    }

    public function getTemplateIds(): array
    {
        return array_column(self::TEMPLATES, 0);
    }

    public function getTemplate(string $templateId): ?Template
    {
        return $this->cache[$templateId] ?? null;
    }

    public function resetTemplate(string $templateId): bool
    {
        if (!isset($this->cache[$templateId])) {
            return false;
        }

        $this->cache[$templateId]->cleanup();
        unset($this->cache[$templateId]);
        error_log("Template '{$templateId}' removed from cache");
        return true;
    }

    public function getTemplatePath(string $templateId): ?string
    {
        foreach (self::TEMPLATES as [$id, $version]) {
            if ($id === $templateId) {
                $path = __DIR__ . "/../templates/{$templateId}-{$version}.zip";
                return file_exists($path) ? $path : null;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return string PDF bytes
     */
    public function compileTemplate(string $templateId, ?array $body, BlobService $blobService): string
    {
        $template = $this->getTemplate($templateId);
        if ($template === null) {
            throw new \InvalidArgumentException("Template '{$templateId}' not found!");
        }

        [$jsonInputs, $blobInputs] = $this->resolveInputs($body, $blobService);

        $start = hrtime(true);
        $result = $template->export(
            jsonInputs: $jsonInputs,
            blobInputs: $blobInputs,
            exportFormat: ExportFormat::pdf(),
            mode: CompilationMode::Production
        );
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        error_log(sprintf("Compiled '%s' to PDF in %.1fms", $templateId, $elapsed));

        return $result;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return string PNG bytes
     */
    public function previewTemplate(string $templateId, ?array $body, BlobService $blobService): string
    {
        $template = $this->getTemplate($templateId);
        if ($template === null) {
            throw new \InvalidArgumentException("Template '{$templateId}' not found!");
        }

        [$jsonInputs, $blobInputs] = $this->resolveInputs($body, $blobService);

        $start = hrtime(true);
        $result = $template->export(
            jsonInputs: $jsonInputs,
            blobInputs: $blobInputs,
            exportFormat: ExportFormat::png(pixelsPerPt: 1.0),
            mode: CompilationMode::Development
        );
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        error_log(sprintf("Compiled '%s' to PNG in %.1fms", $templateId, $elapsed));

        return $result;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{array<string, string>, array<string, BlobInput>}
     */
    private function resolveInputs(?array $body, BlobService $blobService): array
    {
        $jsonInputs = [];
        foreach ($body['jsonInputs'] ?? [] as $input) {
            $jsonInputs[$input['key']] = json_encode($input['value']);
        }

        $blobInputs = [];
        foreach ($body['blobInputs'] ?? [] as $input) {
            $blobId = Uuid::fromString($input['blobId']);
            $blobData = $blobService->getBlob($blobId);
            if ($blobData === null) {
                throw new \InvalidArgumentException(
                    "Blob with id {$input['blobId']} not found. Please use an ID of a blob that was previously uploaded."
                );
            }
            $blobInputs[$input['key']] = new BlobInput($blobData);
        }

        return [$jsonInputs, $blobInputs];
    }

    public function __destruct()
    {
        // Cleanup is handled at process shutdown, not per-instance
        // This prevents premature cleanup when using shared cache
    }
}
