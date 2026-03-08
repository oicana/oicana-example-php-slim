<?php

declare(strict_types=1);

namespace App;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class BlobService
{
    private const DEFAULT_BLOB_UUID = '00000000-0000-0000-0000-000000000000';

    /** @var array<string, string> */
    private array $cache = [];
    private string $blobDir;

    public function __construct()
    {
        $this->blobDir = __DIR__ . '/../blobs';
        $this->initializeDefaultBlob();
    }

    private function initializeDefaultBlob(): void
    {
        $blobPath = $this->blobDir . '/' . self::DEFAULT_BLOB_UUID;

        try {
            if (file_exists($blobPath)) {
                $data = file_get_contents($blobPath);
                if ($data !== false) {
                    $this->cache[self::DEFAULT_BLOB_UUID] = $data;
                    error_log("Loaded default blob (Oicana logo) with UUID " . self::DEFAULT_BLOB_UUID);
                }
            }
        } catch (\Exception $e) {
            error_log("Failed to load default blob from {$blobPath}: {$e->getMessage()}");
        }
    }

    public function getBlob(UuidInterface $blobId): ?string
    {
        $blobIdStr = $blobId->toString();

        if (isset($this->cache[$blobIdStr])) {
            return $this->cache[$blobIdStr];
        }

        $blobPath = $this->blobDir . '/' . $blobIdStr;

        try {
            if (file_exists($blobPath)) {
                $data = file_get_contents($blobPath);
                if ($data !== false) {
                    $this->cache[$blobIdStr] = $data;
                    error_log("Loaded blob {$blobIdStr} from disk and cached it");
                    return $data;
                }
            }
        } catch (\Exception $e) {
            error_log("Failed to read blob {$blobIdStr} from {$blobPath}: {$e->getMessage()}");
        }

        return null;
    }

    public function uploadBlob(string $data): UuidInterface
    {
        $blobId = Uuid::uuid4();
        $blobIdStr = $blobId->toString();
        $blobPath = $this->blobDir . '/' . $blobIdStr;

        if (!is_dir($this->blobDir)) {
            mkdir($this->blobDir, 0755, true);
        }

        if (file_put_contents($blobPath, $data) === false) {
            throw new \RuntimeException("Failed to save blob to disk");
        }

        $this->cache[$blobIdStr] = $data;
        error_log("Stored blob {$blobIdStr} to disk and cache");

        return $blobId;
    }
}
