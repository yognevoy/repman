<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Query\Admin\UserQuery;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Service\Config;
use Buddy\Repman\Validator\NotOrganizationMember;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;

final class InviteMemberType extends AbstractType
{
    private Config $config;
    private UserQuery $userQuery;
    private Security $security;

    public function __construct(Config $config, UserQuery $userQuery, Security $security)
    {
        $this->config = $config;
        $this->userQuery = $userQuery;
        $this->security = $security;
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->config->isDirectUserAdditionEnabled()) {
            $currentUserId = 0;
            $currentUser = $this->security->getUser();
            if ($currentUser !== null) {
                $currentUserId = $currentUser->getUserIdentifier();
            }

            $allUsers = $this->userQuery->findAll(new Filter());
            $users = [];

            foreach ($allUsers as $user) {
                if ($user->id() !== $currentUserId) {
                    $users[$user->email()] = $user->id();
                }
            }

            $builder
                ->add('user_id', ChoiceType::class, [
                    'label' => 'User',
                    'choices' => $users,
                    'placeholder' => 'Choose a user',
                    'constraints' => [
                        new NotNull(),
                    ],
                    'attr' => [
                        'class' => 'form-control selectpicker',
                        'data-style' => 'btn-secondary',
                    ],
                ]);
        } else {
            $builder
                ->add('email', EmailType::class, [
                    'constraints' => [
                        new NotNull(),
                        new Email(['mode' => 'html5']),
                        new NotOrganizationMember(['organizationId' => $options['organizationId']]),
                    ],
                ]);
        }

        $builder->add('role', ChoiceType::class, [
            'choices' => array_combine(Member::availableRoles(), Member::availableRoles()),
            'constraints' => [
                new NotNull(),
            ],
            'attr' => [
                'class' => 'form-control selectpicker',
                'data-style' => 'btn-secondary',
            ],
        ]);

        $builder->add('invite', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('organizationId')->addAllowedTypes('organizationId', 'string');
    }
}
