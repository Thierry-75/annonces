<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\IntraController;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
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
    /**
     * @throws ExceptionInterface
     */
    #[Route('/register', name: 'app_register',methods: ['GET','POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,
       EntityManagerInterface $entityManager, ValidatorInterface $validator,IntraController $intraController,
    JwtService $jwtService,MessageBusInterface $messageBus
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class,$user);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('registration/register.html.twig',['registrationForm'=>$form->createView(),'errors'=>$errors]);
            }
        }
        if($form->isSubmitted() && $form->isValid()){
            $user->setPassword($userPasswordHasher->hashPassword($user,$form->get('plainPassword')->getData()))
                ->setRoles(['ROLE_USER'])
                ->setCreatedAt(new \DateTimeImmutable());
            try {
                $entityManager->persist($user);
                $entityManager->flush();
            }catch (EntityNotFoundException $e) {
                return $this->redirectToRoute('app_error', ['exception'=>$e]);
            }
            try {
                $subject = "Activation de votre compte";
                $destination = 'check_user';
                $nomTemplate = 'register';
                $intraController->emailValidate($user, $jwtService, $messageBus, $destination, $subject, $nomTemplate);
                $this->addFlash('alert-warning', 'Confirm your address email, please.');
                return $this->redirectToRoute('app_home');
            }catch(Exception $e){
                return $this->redirectToRoute('app_error',['exception'=>$e]);
            }

        }
        return $this->render('registration/register.html.twig',['registrationForm'=>$form->createView()]);
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
                $this->addFlash('alert-success','Count created');
                return $this->redirectToRoute('app_login');
            }catch(EntityNotFoundException $e){
                return $this->redirectToRoute('app_error',['exception'=>$e]);
            }
        }
        $this->addFlash('alert-danger','Token off !');
        return $this->redirectToRoute('app_login');

    }
}
