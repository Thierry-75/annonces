<?php

namespace App\DataFixtures;

use App\Entity\Annonce;
use App\Entity\Categorie;
use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AnnonceFixtures extends Fixture
{
    public function __construct( private UserPasswordHasherInterface  $userPasswordHasher,private SluggerInterface $slugger)
    {}

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        // photos-annonces
        $random_photos = ['default1.jpeg','default2.jpeg','default3.jpeg','default4.jpeg',
            'default5.jpeg','default6.jpeg','default7.jpeg','default8.jpeg','default9.jpeg','default10.jpeg'];

        // Categories
        $categories = [
            1 => [
                'name' => 'Véhicules',
                'color' => '#F0FFFF'
            ],
            2 => [
                'name' => 'Mobilier',
                'color' => '#FFE4C4'
            ],
            3 => [
                'name' => 'Outils',
                'color' => '#BDB76B'
            ],
            4 => [
                'name' => 'Immobilier',
                'color' => '#E6E6FA'
            ],
            5 => [
                'name' => 'Informatique',
                'color' => '#FAF0E6'
            ],
            6 => [
                'name' => 'Loisirs',
                'color' => '#F5FFFA'
            ],
            7 => [
                'name' => 'Mode',
                'color' => '#C0C0C0'
            ],
        ];
        $types=[];
        foreach($categories as $key => $value ) {
            $categorie = new Categorie();
            $categorie->setName($value['name'])
                ->setSlug($this->slugger->slug(strtolower($categorie->getName())))
                ->setColor($value['color']);
            $manager->persist($categorie);
            $types[]=(object)$categorie;

        }

        for($nbUsers = 0; $nbUsers <= 50; $nbUsers++)
        {
            $user = new User();
            $user->setEmail($faker->email)
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->userPasswordHasher->hashPassword($user,'ArethiA75!'))
                ->setName($faker->lastName)
                ->setFirstname(random_int(0,1) === 1 ? $faker->firstNameFemale:$faker->firstName)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setIsVerified(random_int(0,1)===1 ? 1:0);
            if($user->getIsVerified()){
                for($nbAnnonce = 0; $nbAnnonce < 3; $nbAnnonce++)
                {
                    $announce = new Annonce();
                    $announce->setUser($user)
                        ->setCategorie($types[random_int(0,count($types)-1)])
                        ->setTitle($faker->realText(25))
                        ->setSlug($this->slugger->slug(strtolower($announce->getTitle())))
                        ->setContent($faker->realText(400))
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setActive($faker->numberBetween(0,1));
                    for($img = 1; $img < 3; $img++)
                    {
                        $photo = new Photo();
                        $photo->setName('default' . random_int(1,10). '.jpeg ');
                        $announce->addPhoto($photo);
                    }
                    $manager->persist($announce);
                    sleep(1);
                }
            }
            $manager->persist($user);
        }

        $manager->flush();
    }


}
