<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

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
            ->add('photos',FileType::class, options: [
                'multiple'=>true,
                'mapped'=>false,
                'attr'=>['class'=>'w-full bg-white px-3 py-3.5 mt-3 mb-4 text-xl rounded-lg shadow-md shadow-black'],
                'label'=>'Upload 3 photos',
                'label_attr' => ['class' => 'block text-lg font-medium text-cyan-800 mb-1 ml-2'],
                'constraints'=>[
                    new Sequentially([
                        new NotBlank(message: '3 Photos '),
                        new Count(
                            min: 1,
                            max: 3,
                            exactMessage: '3 photos !',
                            minMessage: '3 photos SVP',
                            maxMessage: '3 photos SVP'
                        )
                    ])
                ]
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
