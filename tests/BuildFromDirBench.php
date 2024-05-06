<?php

declare(strict_types=1);

namespace PharBuildBench;

use Phar;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use RuntimeException;
use function bin2hex;
use function extension_loaded;
use function file_exists;
use function ini_get;
use function is_dir;
use function mkdir;
use function random_bytes;
use function unlink;
use const DIRECTORY_SEPARATOR;

final class BuildFromDirBench
{
    private const string DEST_DIR = __DIR__ . '/../dist/build-from-dir';
    private const string SOURCE_DIR = __DIR__ . '/../dist/source';

    private string $pharPath;
    private Phar $phar;

    public function setUp(): void
    {
        self::assertXdebugIsDisabled();
        self::assertPharReadonlyDisabled();
        self::assertSourceDirectoryExists();
        self::createDestinationDirectoryIfNecessary();

        $this->pharPath = self::createPharPath();
        $this->phar = new Phar($this->pharPath);
    }

    public function tearDown(): void
    {
        unset($this->phar);
        @unlink($this->pharPath);
    }

    #[Iterations(10)]
    #[BeforeMethods('setUp')]
    #[AfterMethods('tearDown')]
    public function bench(): void
    {
        $this->phar->buildFromDirectory(self::SOURCE_DIR);
    }

    private static function assertXdebugIsDisabled(): void
    {
        if (extension_loaded('xdebug')) {
            throw new RuntimeException('Xdebug should be disabled for benchmarks.');
        }
    }

    private static function assertPharReadonlyDisabled(): void
    {
        $pharReadonly = '1' === ini_get('phar.readonly');

        if ($pharReadonly) {
            throw new RuntimeException('The setting phar.readonly=0 should be set.');
        }
    }

    private static function createDestinationDirectoryIfNecessary(): void
    {
        if (!file_exists(self::DEST_DIR)) {
            $directoryCreated = mkdir(self::DEST_DIR);

            if (!$directoryCreated) {
                throw new RuntimeException('Could not create destination directory.');
            }
        }
    }

    private static function assertSourceDirectoryExists(): void
    {
        if (!file_exists(self::SOURCE_DIR) && !is_dir(self::SOURCE_DIR)) {
            throw new RuntimeException('The source directory does not exist.');
        }
    }

    private static function createPharPath(): string
    {
        return self::DEST_DIR . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.phar';
    }
}