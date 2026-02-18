<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DinaController extends AbstractController
{
    #[Route('/', name: 'app_dina')]
    public function index(): Response
    {
        return $this->render('dina/index.html.twig');
    }
    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('dina/about.html.twig');
    }
    #[Route('/widgets', name: 'app_widgets')]
    public function widgets(): Response
    {
        return $this->render('dina/widgets.html.twig');
    }
    #[Route('/solutions', name: 'app_solutions')]
    public function solutions(): Response
    {
        return $this->render('dina/solutions.html.twig');
    }
    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('dina/pricing.html.twig');
    }
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('dina/contact.html.twig');
    }
    
}
