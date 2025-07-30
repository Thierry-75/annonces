<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\IntraController;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register',methods: ['GET','POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,
       EntityManagerInterface $entityManager, ValidatorInterface $validator,IntraController $intraController,
    JwtService $jwtService,MessageBusInterface $messageBus
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('registration/register.html.twig', ['registrationForm' => $form->createView(),'errors'=>$errors]);
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword encode the plain password **/
            $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()))
                 ->setRoles(['ROLE_USER']);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            }catch(EntityNotFoundException $e){
                return $this->redirectToRoute('app_error',['exception'=>$e]);
            }
            // send an email with rabbitmq async
            try {
                $intraController->emailValidate($user, $jwtService, $messageBus, 'check_user', 'Ouverture de votre compte', 'register');
            } catch (ExceptionInterface) {
            }
            $this->addFlash('alert-warning','Veuillez confirmer votre adresse email.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', ['registrationForm' => $form->createView(),]);
    }

    #[Route('/check/{token}',name:'check_user')]
    public function verifyUser($token, JwtService $jwtService, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // if token valid, expired & !modified
        if($jwtService->isValid($token) && !$jwtService->isExpired($token) && $jwtService->check($token, $this->getParameter('app.jwtsecret'))){
            $payload = $jwtService->getPayload($token);
            //user token
            try{
                $user = $userRepository->find($payload['user_id']);
                $user->setIsVerified(true);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('alert-success','Compte activé');
                return $this->redirectToRoute('app_login');
            }catch(EntityNotFoundException $e){
                return $this->redirectToRoute('app_error',['exception'=>$e]);
            }
        }
        $this->addFlash('alert-danger','Token expiré !');
        return $this->redirectToRoute('app_login');

    }
}
