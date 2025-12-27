<?php

declare(strict_types=1);

namespace BlockBuilder\Environment;

final class RuntimeDirectory
{
    private const string DIRECTORY_NAME = 'block_builder';
    private const string LOCKS_DIRECTORY_NAME = 'locks';
    private const string BACKUPS_DIRECTORY_NAME = 'backups';

    private function __construct()
    {
    }

    public static function getPath(): string
    {
        return DIR_FILES_UPLOADED_STANDARD
            . DIRECTORY_SEPARATOR
            . self::DIRECTORY_NAME;
    }

    public static function getLocksPath(): string
    {
        return self::getPath()
            . DIRECTORY_SEPARATOR
            . self::LOCKS_DIRECTORY_NAME;
    }

    public static function getBackupsPath(): string
    {
        return self::getPath()
            . DIRECTORY_SEPARATOR
            . self::BACKUPS_DIRECTORY_NAME;
    }
}
