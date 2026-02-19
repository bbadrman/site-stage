<?php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'attr' => ['class'=>'contact-input','placeholder'=>'Full name']
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class'=>'contact-input','placeholder'=>'Email']
            ])
            ->add('company', TextType::class, [
                'required'=>false,
                'attr' => ['class'=>'contact-input','placeholder'=>'Company']
            ])
            ->add('message', TextareaType::class, [
                'attr' => ['class'=>'contact-textarea','rows'=>7,'placeholder'=>'Tell us what you have in mind...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
