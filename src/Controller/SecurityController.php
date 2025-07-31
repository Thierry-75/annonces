<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\RequestPasswordType;
use App\Message\SendActivationMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    protected const WEBMASTER = 'webmaster@annonces.fr';
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('app_home');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path:'/forgotten-password',name: 'forgotten_password',methods: ['GET','POST'])]
    public function forgottenPassword(Request $request, ValidatorInterface $validator,
                                      UserRepository $userRepository, EntityManagerInterface $entityManager,
                                      TokenGeneratorInterface $tokenGenerator, MessageBusInterface $messageBus
    ):Response
    {
        $form = $this->createForm(RequestPasswordType::class);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('security/reset_password_request.html.twig',['requestForm'=>$form->createView(),'errors'=>$errors]);
            }
        }
        if( $form->isSubmitted() && $form->isValid()) {
            try{
                $user = $userRepository->findByEmail($form->get('email')->getData());
                $inscrit = (object)$user[0];
                $token = $tokenGenerator->generateToken();
                $inscrit->setResetToken($token);
                $entityManager->flush();
                $url = $this->generateUrl('reset_password',['token'=>$token], UrlGeneratorInterface::ABSOLUTE_URL);
                $messageBus->dispatch(new SendActivationMessage(self::WEBMASTER,$inscrit->getEmail(),'Demande de nouveau mot de passe','password_reset',['url'=>$url,'user'=>$inscrit]));
                $this->addFlash('alert-warning',"Lien d'activation nouveau mot de passe envoyé.");
                return $this->redirectToRoute('app_home');
            }catch(EntityNotFoundException $e)
            {
                return $this->redirectToRoute('app_error',['exception'=>$e]);
            }
        }
        return $this->render('security/reset_password_request.html.twig',['requestForm'=>$form->createView()]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/lost-password/{token}',name: 'reset_password')]
    public function resetPassword(
        string $token,Request $request,UserRepository $userRepository,ValidatorInterface $validator,EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher, MessageBusInterface $messageBus
    ):Response
    {
        try {
            $user = $userRepository->findByResetToken($token);
            $inscrit = (object) $user[0];
        }catch(EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
            if(isset($inscrit)){
                $form = $this->createForm(ChangePasswordType::class);
                $form->handleRequest($request);
                if($request->isMethod('POST')){
                    $errors = $validator->validate($request);
                    if(count($errors)>0){
                        return $this->render('/security/reset.html.twig',['resetForm'=>$form->createView(),'errors'=>$errors]);
                    }
                    if($form->isSubmitted() && $form->isValid()){
                        $inscrit->setPassword($userPasswordHasher->hashPassword($inscrit,$form->get('plainPassword')->getData()))
                                ->setUpdatedAt(new \DateTimeImmutable())
                                ->setResetToken('');
                        try{
                            $entityManager->persist($inscrit);
                            $entityManager->flush();
                            $url = $this->generateUrl('app_home',[], UrlGeneratorInterface::ABSOLUTE_URL);
                            $messageBus->dispatch(new SendActivationMessage(self::WEBMASTER,$inscrit->getEmail(),'Nouveau mot de passe','new_password',
                            ['user'=>$inscrit, 'url'=>$url]));
                            $this->addFlash('alert-success','Votre mot de passe a été modifié.');
                            return $this->redirectToRoute('app_login');
                        }catch(EntityNotFoundException $e){
                            return $this->redirectToRoute('app_error',['exception'=>$e]);
                        }
                }
            }
        }
        return $this->render('/security/reset.html.twig',['resetForm'=>$form->createView()]);
    }
}
