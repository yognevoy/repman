<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class Update
{
    private string $packageId;
    private string $url;
    private int $keepLastReleases;
    private bool $enableSecurityScan;
    private bool $locked;
    private ?string $lockedVersion;
    private ?\DateTimeImmutable $lockedUntil;

    public function __construct(
        string $packageId,
        string $url,
        int $keepLastReleases,
        bool $enableSecurityScan,
        bool $locked = false,
        ?string $lockedVersion = null,
        ?\DateTimeImmutable $lockedUntil = null
    ) {
        $this->packageId = $packageId;
        $this->url = $url;
        $this->keepLastReleases = $keepLastReleases;
        $this->enableSecurityScan = $enableSecurityScan;
        $this->locked = $locked;
        $this->lockedVersion = $lockedVersion;
        $this->lockedUntil = $lockedUntil;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function keepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function lockedVersion(): ?string
    {
        return $this->lockedVersion;
    }

    public function lockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }
}
