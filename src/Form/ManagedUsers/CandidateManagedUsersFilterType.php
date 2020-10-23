<?php

namespace App\Form\ManagedUsers;

use App\Entity\ReferentTag;
use App\Form\GenderType;
use App\Form\MyReferentTagChoiceType;
use App\ManagedUsers\ManagedUsersFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidateManagedUsersFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gender', GenderType::class, [
                'placeholder' => 'Tous',
                'expanded' => true,
                'required' => false,
            ])
            ->add('ageMin', IntegerType::class, ['required' => false])
            ->add('ageMax', IntegerType::class, ['required' => false])
            ->add('firstName', TextType::class, ['required' => false])
            ->add('lastName', TextType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('emailSubscription', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'common.all' => null,
                    'common.adherent.subscribed' => true,
                    'common.adherent.unsubscribed' => false,
                ],
                'choice_value' => function ($choice) {
                    return false === $choice ? '0' : (string) $choice;
                },
            ])
            ->add('smsSubscription', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'common.all' => null,
                    'common.adherent.subscribed' => true,
                    'common.adherent.unsubscribed' => false,
                ],
                'choice_value' => function ($choice) {
                    return false === $choice ? '0' : (string) $choice;
                },
            ])
            ->add('referentTags', MyReferentTagChoiceType::class, [
                'placeholder' => 'Tous',
                'required' => false,
                'by_reference' => false,
            ])
            ->add('sort', HiddenType::class, ['required' => false])
            ->add('order', HiddenType::class, ['required' => false])
        ;

        $referentTagsField = $builder->get('referentTags');

        $referentTagsField->addModelTransformer(new CallbackTransformer(
            static function ($value) use ($referentTagsField) {
                if (\is_array($value) && \count($value) === \count($referentTagsField->getOption('choices'))) {
                    return null;
                }

                return $value;
            },
            static function ($value) use ($referentTagsField) {
                if (null === $value) {
                    return  $referentTagsField->getOption('choices');
                }

                if ($value instanceof ReferentTag) {
                    return [$value];
                }

                return $value;
            },
        ));
    }

    public function getBlockPrefix()
    {
        return 'f';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => ManagedUsersFilter::class,
                'allow_extra_fields' => true,
            ])
        ;
    }
}
