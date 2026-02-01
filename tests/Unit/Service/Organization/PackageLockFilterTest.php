<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\PackageQuery\Filter;
use Buddy\Repman\Service\Organization\PackageLockFilter;
use Munus\Control\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PackageLockFilterTest extends TestCase
{
    /**
     * @var MockObject&PackageQuery
     */
    private PackageQuery $packageQuery;
    private PackageLockFilter $filter;

    protected function setUp(): void
    {
        $this->packageQuery = $this->createMock(PackageQuery::class);
        $this->filter = new PackageLockFilter($this->packageQuery);
    }

    public function testFilterDoesNotRemoveNonLockedPackages(): void
    {
        $organizationId = 'org-123';
        $packages = [
            'test/package' => [
                '1.0.0' => ['version' => '1.0.0'],
                '2.0.0' => ['version' => '2.0.0'],
            ],
        ];

        $lockedPackage = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            'locked/package',
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            false,
            null,
            null
        );

        $nonLockedPackage = new Package(
            'pkg-456',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            'test/package',
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            false,
            null,
            null
        );

        $this->packageQuery->expects(self::once())
            ->method('findAll')
            ->with($organizationId, self::isInstanceOf(Filter::class))
            ->willReturn([$nonLockedPackage, $lockedPackage]);

        $result = $this->filter->filter($packages, $organizationId);

        self::assertEquals($packages, $result);
    }

    public function testFilterRemovesVersionsGreaterThanMaxVersion(): void
    {
        $organizationId = 'org-123';
        $packages = [
            'test/package' => [
                '1.0.0' => ['version' => '1.0.0'],
                '1.2.0' => ['version' => '1.2.0'],
                '2.0.0' => ['version' => '2.0.0'], // This should be removed
            ],
        ];

        $lockedPackage = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            'test/package',
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            true,
            '1.5.0',
            null
        );

        $this->packageQuery->expects(self::once())
            ->method('findAll')
            ->with($organizationId, self::isInstanceOf(Filter::class))
            ->willReturn([$lockedPackage]);

        $result = $this->filter->filter($packages, $organizationId);

        $expected = [
            'test/package' => [
                '1.0.0' => ['version' => '1.0.0'],
                '1.2.0' => ['version' => '1.2.0'],
                // '2.0.0' should be removed
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testFilterRemovesVersionsReleasedAfterMaxReleaseDate(): void
    {
        $organizationId = 'org-123';
        $packages = [
            'test/package' => [
                '1.0.0' => ['version' => '1.0.0', 'time' => '2024-01-01T00:00:00+00:00'],
                '1.1.0' => ['version' => '1.1.0', 'time' => '2025-01-01T00:00:00+00:00'], // This should be removed
                '1.2.0' => ['version' => '1.2.0', 'time' => '2026-01-01T00:00:00+00:00'], // This should be removed
            ],
        ];

        $maxReleaseDate = new \DateTimeImmutable('2024-06-01T00:00:00+00:00');

        $lockedPackage = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            'test/package',
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            true,
            null,
            $maxReleaseDate
        );

        $this->packageQuery->expects(self::once())
            ->method('findAll')
            ->with($organizationId, self::isInstanceOf(Filter::class))
            ->willReturn([$lockedPackage]);

        $result = $this->filter->filter($packages, $organizationId);

        $expected = [
            'test/package' => [
                '1.0.0' => ['version' => '1.0.0', 'time' => '2024-01-01T00:00:00+00:00'],
                // '1.1.0' and '1.2.0' should be removed
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testIsVersionAllowedReturnsTrueForNonExistentPackage(): void
    {
        $organizationId = 'org-123';
        $packageName = 'non-existent/package';
        $version = '1.0.0';

        $this->packageQuery->expects(self::once())
            ->method('getByName')
            ->with($organizationId, $packageName)
            ->willReturn(Option::none());

        $result = $this->filter->isVersionAllowed($organizationId, $packageName, $version);

        self::assertTrue($result);
    }

    public function testIsVersionAllowedReturnsTrueForNonLockedPackage(): void
    {
        $organizationId = 'org-123';
        $packageName = 'test/package';
        $version = '1.0.0';

        $package = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            $packageName,
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            false,
            null,
            null
        );

        $this->packageQuery->expects(self::once())
            ->method('getByName')
            ->with($organizationId, $packageName)
            ->willReturn(Option::some($package));

        $result = $this->filter->isVersionAllowed($organizationId, $packageName, $version);

        self::assertTrue($result);
    }

    public function testIsVersionAllowedReturnsFalseForVersionGreaterThanMaxVersion(): void
    {
        $organizationId = 'org-123';
        $packageName = 'test/package';
        $version = '2.0.0';

        $package = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            $packageName,
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            true,
            '1.5.0',
            null
        );

        $this->packageQuery->expects(self::once())
            ->method('getByName')
            ->with($organizationId, $packageName)
            ->willReturn(Option::some($package));

        $result = $this->filter->isVersionAllowed($organizationId, $packageName, $version);

        self::assertFalse($result);
    }

    public function testIsVersionAllowedReturnsTrueForVersionLessThanOrEqualToMaxVersion(): void
    {
        $organizationId = 'org-123';
        $packageName = 'test/package';
        $version = '1.0.0';

        $package = new Package(
            'pkg-123',
            'org-123',
            'vcs',
            'https://github.com/test/test',
            $packageName,
            '1.0.0',
            null,
            'Test package',
            null,
            null,
            null,
            null,
            null,
            0,
            true,
            false,
            false,
            '1.5.0',
            null
        );

        $this->packageQuery->expects(self::once())
            ->method('getByName')
            ->with($organizationId, $packageName)
            ->willReturn(Option::some($package));

        $result = $this->filter->isVersionAllowed($organizationId, $packageName, $version);

        self::assertTrue($result);
    }
}
