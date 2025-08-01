<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-administrator',
    description: 'Add a short description for your command',
    help: 'Allow you to create an administrator count'
)]
class CreateAdministratorCommand extends Command
{

    public function __construct(private readonly EntityManagerInterface $entityManager,private readonly UserPasswordHasherInterface $userPasswordHasher )
    {
        parent::__construct('app:create-administrator');

    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Address email')
            ->addArgument('password',InputArgument::OPTIONAL,'Password')
            ->addArgument('name',InputArgument::OPTIONAL,'Name')
            ->addArgument('firstname',InputArgument::OPTIONAL,'Firstname')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Write your address email : ');
            $email = $helper->ask($input,$output,$question);
        }
        $name = $input->getArgument('name');
        if (!$name) {
            $question = new Question('Write your name : ');
            $name = $helper->ask($input,$output,$question);
        }
        $firstname = $input->getArgument('firstname');
        if (!$firstname) {
            $question = new Question('Write your firstname : ');
            $firstname = $helper->ask($input,$output,$question);
        }
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Write your password : ');
            $plainPassword = $helper->ask($input,$output,$question);
        }

        $admin = new User();
        $admin->setEmail($email)
               ->setName($name)
               ->setFirstname($firstname)
              ->setPassword($this->userPasswordHasher->hashPassword($admin,$plainPassword));
        $admin->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsVerified(true);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();


        $io->success('You have created an administrator count, don\'t forget to write clear in shell then enter !');
        return Command::SUCCESS;
    }
}
