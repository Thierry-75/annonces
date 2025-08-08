<?php

namespace App\Controller;

use App\Form\SearchAnnonceType;
use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    /**
     * @param AnnonceRepository $annonceRepository
     * @param Request $request
     * @return Response
     */
    #[Route('/', name: 'app_home')]
    public function index(AnnonceRepository $annonceRepository,Request $request): Response
    {
        $annonces =$annonceRepository->findBy(['active'=>true],['createdAt'=>'desc']);
        $form = $this->createForm(SearchAnnonceType::class);
        $search = $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $annonces = $annonceRepository->search(
                $search->get('word')->getData(),
                $search->get('category')->getData());
        }

        return $this->render('main/index.html.twig', [
            'announces'=>$annonces,
        'form'=>$form->createView()
        ]);
    }
}
