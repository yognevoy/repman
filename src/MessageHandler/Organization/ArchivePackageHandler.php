<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\ArchivePackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ArchivePackageHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private PackageRepository $packages;

    public function __construct(OrganizationRepository $organizations, PackageRepository $packages)
    {
        $this->organizations = $organizations;
        $this->packages = $packages;
    }

    public function __invoke(ArchivePackage $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->id()));
        $this->organizations->getById(Uuid::fromString($message->organizationId()));

        $package->archive();
    }
}
