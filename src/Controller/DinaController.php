<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DinaController extends AbstractController
{
    #[Route('/dina', name: 'app_dina')]
    public function index(): Response
    {
        return $this->render('dina/index.html.twig', [
            'controller_name' => 'DinaController',
        ]);
    }
}
