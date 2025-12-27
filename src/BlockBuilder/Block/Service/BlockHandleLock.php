<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

final class BlockHandleLock
{
    /** @var resource|null */
    private mixed $stream;

    /** @param resource $stream */
    public function __construct(mixed $stream)
    {
        $this->stream = $stream;
    }

    public function release(): void
    {
        if (!is_resource($this->stream)) {
            return;
        }

        flock($this->stream, LOCK_UN);
        fclose($this->stream);
        $this->stream = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}
