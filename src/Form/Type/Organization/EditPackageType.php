<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class EditPackageType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'Repository URL',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('keepLastReleases', IntegerType::class, [
                'label' => 'Keep last releases',
                'help' => 'Number of last releases that will be downloaded. Put "0" to download all.',
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(),
                ],
            ])
            ->add('enableSecurityScan', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('locked', ChoiceType::class, [
                'label' => 'Enable Lock',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'required' => true,
                'help' => 'Enable lock to restrict package versions',
            ])
            ->add('lockedVersion', TextType::class, [
                'label' => 'Lock to version (optional)',
                'required' => false,
                'help' => 'Maximum version allowed. Leave empty to disable version lock.',
            ])
            ->add('lockedUntil', TextType::class, [
                'label' => 'Lock until (optional)',
                'required' => false,
                'help' => 'Date until which the package will be locked. Leave empty to disable date lock.',
            ])
            ->add('Update', SubmitType::class);
    }
}
