<?php

declare(strict_types=1);

namespace BlockBuilder\Service;

use Concrete\Core\File\Service\File as FileService;
use Symfony\Component\Filesystem\Filesystem;

readonly class FileSystemService
{
    public function __construct(
        private FileService $fileService,
    ) {
    }

    public function removeDirectory($dir, $excluded = []): void
    {
        $items = $this->fileService->getDirectoryContents($dir, $excluded);

        foreach ($items as $item) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->fileService->removeAll($fullPath, true);
            } else {
                $fs = new Filesystem();
                $fs->remove($fullPath);
            }
        }
    }
}
