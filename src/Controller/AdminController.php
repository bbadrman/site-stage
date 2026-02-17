<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_ADMIN")]
class AdminController extends AbstractController
{
    #[Route("/admin/dashboard", name: "app_admin_dashboard")]
    public function dashboard(): Response
    {
        return $this->render("admin/index.html.twig", [
            "controller_name" => "AdminController",
        ]);
    }
}
