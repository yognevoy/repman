<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\Organization\ArchivePackage;
use Buddy\Repman\MessageHandler\Organization\ArchivePackageHandler;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\PackageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ArchivePackageHandlerTest extends TestCase
{
    /**
     * @var MockObject&PackageRepository
     */
    private PackageRepository $packageRepository;

    /**
     * @var MockObject&OrganizationRepository
     */
    private OrganizationRepository $organizationRepository;

    private ArchivePackageHandler $handler;

    protected function setUp(): void
    {
        $this->packageRepository = $this->createMock(PackageRepository::class);
        $this->organizationRepository = $this->createMock(OrganizationRepository::class);
        $this->handler = new ArchivePackageHandler($this->organizationRepository, $this->packageRepository);
    }

    public function testArchivePackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $organizationId = Uuid::uuid4()->toString();

        $package = new Package(Uuid::fromString($packageId), 'vcs', 'http://url');

        $user = new User(Uuid::uuid4(), 'test@buddy.works', 'token', []);
        $organization = new Organization(Uuid::uuid4(), $user, 'repman', 'repman');

        $this->packageRepository->expects(self::once())
            ->method('getById')
            ->with(Uuid::fromString($packageId))
            ->willReturn($package);

        $this->organizationRepository->expects(self::once())
            ->method('getById')
            ->with(Uuid::fromString($organizationId))
            ->willReturn($organization);

        $message = new ArchivePackage($packageId, $organizationId);
        ($this->handler)($message);

        self::assertTrue($package->isArchived());
    }
}
