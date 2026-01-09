<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Organization;

use Buddy\Repman\Query\Filter as BaseFilter;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Query\User\PackageQuery\Filter;
use Composer\Semver\VersionParser;

class PackageLockFilter
{
    private PackageQuery $packageQuery;
    private VersionParser $versionParser;

    public function __construct(PackageQuery $packageQuery)
    {
        $this->packageQuery = $packageQuery;
        $this->versionParser = new VersionParser();
    }

    /**
     * Filters packages based on lock parameters (version and date constraints).
     *
     * @param array $packages
     * @param string $organizationId
     * @return array
     */
    public function filter(array $packages, string $organizationId): array
    {
        $filteredPackages = $packages;

        $allPackages = $this->packageQuery->findAll($organizationId, (new Filter())->setArchived(false));
        $lockParams = [];

        foreach ($allPackages as $pkg) {
            if ($pkg->isLocked() && $pkg->name() !== null) {
                $lockParams[$pkg->name()] = [
                    'lockedVersion' => $pkg->lockedVersion(),
                    'lockedUntil' => $pkg->lockedUntil(),
                ];
            }
        }

        foreach ($lockParams as $packageName => $params) {
            if (isset($filteredPackages[$packageName])) {
                foreach ($filteredPackages[$packageName] as $versionName => $versionData) {
                    $shouldRemove = false;

                    if ($params['lockedVersion']) {
                        $normalizedVersion = $this->versionParser->normalize($versionName);
                        $normalizedLockedVersion = $this->versionParser->normalize($params['lockedVersion']);

                        if (version_compare($normalizedVersion, $normalizedLockedVersion, '>')) {
                            $shouldRemove = true;
                        }
                    }

                    if ($params['lockedUntil'] && isset($versionData['time'])) {
                        try {
                            $versionDate = new \DateTimeImmutable($versionData['time']);
                            if ($versionDate > $params['lockedUntil']) {
                                $shouldRemove = true;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    if ($shouldRemove) {
                        unset($filteredPackages[$packageName][$versionName]);
                    }
                }
            }
        }

        return $filteredPackages;
    }

    /**
     * Checks if a specific package version is allowed based on lock parameters.
     *
     * @param string $organizationId
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function isVersionAllowed(string $organizationId, string $packageName, string $version): bool
    {
        $packageOption = $this->packageQuery->getByName($organizationId, $packageName);

        if ($packageOption->isEmpty()) {
            return true;
        }

        $package = $packageOption->get();

        if (!$package->isLocked()) {
            return true;
        }

        $lockedVersion = $package->lockedVersion();
        $lockedUntil = $package->lockedUntil();

        if ($lockedVersion !== null) {
            $normalizedVersion = $this->versionParser->normalize($version);
            $normalizedLockedVersion = $this->versionParser->normalize($lockedVersion);

            if (version_compare($normalizedVersion, $normalizedLockedVersion, '>')) {
                return false;
            }
        }

        if ($lockedUntil !== null) {
            $packageId = $package->id();
            $versionModels = $this->packageQuery->getVersions($packageId, new BaseFilter());
            foreach ($versionModels as $versionModel) {
                if ($versionModel->version() === $version) {
                    $versionDate = $versionModel->date();
                    if ($versionDate > $lockedUntil) {
                        return false;
                    }
                    break;
                }
            }
        }

        return true;
    }
}
