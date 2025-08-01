<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\Categorie;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;

class AnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',TextType::class)
            ->add('content',TinymceType::class,['attr'=>["toolbar"=>'undo redo | bold italic | forecolor backcolor |
             template | alignleft aligncenter alignright alignjustify | bullist numlist | link | spellchecker',
                    'height'=>250,'class'=>'w-full']]
            )
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label'=>'Category',
                'placeholder'=>'Choose a category',
                'choice_label' => 'name',
            ])
            ->add('submit',SubmitType::class)
            ->addEventListener(FormEvents::POST_SUBMIT,$this->addDate(...))
        ;
    }

    public function addDate(PostSubmitEvent $event): void
    {
        $data = $event->getData();
        if(!$data instanceof Annonce) {
            return;
        }
        $data->setCreatedAt(new \DateTimeImmutable());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Annonce::class,
        ]);
    }
}
