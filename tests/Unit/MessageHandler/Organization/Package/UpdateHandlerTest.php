<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\MessageHandler\Organization\Package\UpdateHandler;
use Buddy\Repman\Repository\PackageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class UpdateHandlerTest extends TestCase
{
    /**
     * @var MockObject&PackageRepository
     */
    private PackageRepository $packages;
    private UpdateHandler $handler;

    protected function setUp(): void
    {
        $this->packages = $this->createMock(PackageRepository::class);
        $this->handler = new UpdateHandler($this->packages);
    }

    public function testUpdateHandlerUpdatesPackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $url = 'http://url';
        $keepLastReleases = 5;
        $enableSecurityScan = true;
        $locked = true;
        $maxVersion = '1.2.3';
        $maxReleaseDate = new \DateTimeImmutable();

        $package = $this->createMock(Package::class);
        $package->expects(self::once())
            ->method('update')
            ->with(
                $url,
                $keepLastReleases,
                $enableSecurityScan,
                $locked,
                $maxVersion,
                $maxReleaseDate
            );

        $this->packages->expects(self::once())
            ->method('find')
            ->with(Uuid::fromString($packageId))
            ->willReturn($package);

        $message = new Update(
            $packageId,
            $url,
            $keepLastReleases,
            $enableSecurityScan,
            $locked,
            $maxVersion,
            $maxReleaseDate
        );

        ($this->handler)($message);
    }

    public function testUpdateHandlerDoesNothingIfPackageNotFound(): void
    {
        $packageId = Uuid::uuid4()->toString();

        $this->packages->expects(self::once())
            ->method('find')
            ->with(Uuid::fromString($packageId))
            ->willReturn(null);

        $message = new Update(
            $packageId,
            'http://url',
            5,
            true,
            true,
            '1.2.3',
            new \DateTimeImmutable()
        );

        ($this->handler)($message);
    }
}
