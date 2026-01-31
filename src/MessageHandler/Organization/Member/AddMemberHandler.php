<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Member;

use Buddy\Repman\Message\Organization\Member\AddMember;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddMemberHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private UserRepository $users;

    public function __construct(OrganizationRepository $organizations, UserRepository $users)
    {
        $this->organizations = $organizations;
        $this->users = $users;
    }

    public function __invoke(AddMember $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->addMember($this->users->getById(Uuid::fromString($message->userId())), $message->role())
        ;
    }
}
