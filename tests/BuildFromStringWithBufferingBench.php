<?php

declare(strict_types=1);

namespace PharBuildBench;

use Phar;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function array_map;
use function bin2hex;
use function extension_loaded;
use function file_exists;
use function ini_get;
use function is_dir;
use function iterator_to_array;
use function mkdir;
use function random_bytes;
use function unlink;
use function var_dump;
use const DIRECTORY_SEPARATOR;

final class BuildFromStringWithBufferingBench
{
    private const string DEST_DIR = __DIR__ . '/../dist/build-from-dir';
    private const string SOURCE_DIR = __DIR__ . '/../dist/source';

    private array $files;
    private string $pharPath;
    private Phar $phar;

    public function setUp(): void
    {
        self::assertXdebugIsDisabled();
        self::assertPharReadonlyDisabled();
        self::assertSourceDirectoryExists();
        self::createDestinationDirectoryIfNecessary();

        $this->files = array_map(
            self::mapFileToTuple(...),
            iterator_to_array(
                self::createFinder(),
                false,
            ),
        );

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
        $this->phar->startBuffering();

        foreach ($this->files as [$fileName, $fileContents]) {
            $this->phar->addFromString($fileName, $fileContents);
        }

        $this->phar->stopBuffering();
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

    private static function createFinder(): Finder
    {
        return Finder::create()
            ->files()
            ->in(self::SOURCE_DIR)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->ignoreUnreadableDirs(false);
    }

    private static function mapFileToTuple(SplFileInfo $fileInfo): array
    {
        return [
            $fileInfo->getFilename(),
            $fileInfo->getContents(),
        ];
    }
}