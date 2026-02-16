<?php

namespace App\Controller;

use App\Entity\Prosect;
use App\Form\ProsectType;
use App\Repository\ProsectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prosect')]
final class ProsectController extends AbstractController
{
    #[Route(name: 'app_prosect_index', methods: ['GET'])]
    public function index(ProsectRepository $prosectRepository): Response
    {
        return $this->render('prosect/index.html.twig', [
            'prosects' => $prosectRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_prosect_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $prosect = new Prosect();
        $form = $this->createForm(ProsectType::class, $prosect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($prosect);
            $entityManager->flush();

            return $this->redirectToRoute('app_prosect_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prosect/new.html.twig', [
            'prosect' => $prosect,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_prosect_show', methods: ['GET'])]
    public function show(Prosect $prosect): Response
    {
        return $this->render('prosect/show.html.twig', [
            'prosect' => $prosect,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_prosect_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Prosect $prosect, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProsectType::class, $prosect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_prosect_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prosect/edit.html.twig', [
            'prosect' => $prosect,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_prosect_delete', methods: ['POST'])]
    public function delete(Request $request, Prosect $prosect, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$prosect->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($prosect);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_prosect_index', [], Response::HTTP_SEE_OTHER);
    }
}
