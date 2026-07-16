<?php

namespace App\Controller;

use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    public function index(CustomerOrderRepository $customerOrderRepository): Response
    {
        $user = $this->getUser();

        // Si aucun utilisateur n'est connecté, on le redirige vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // On récupère les commandes de l'utilisateur connecté et on tri par ordre croissant
        $orders = $customerOrderRepository->findBy(
            ['user' => $user],
            ['validateAt' => 'ASC']
        );

        return $this->render('account/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/mon-compte/acces-api', name: 'app_account_toggle_api_access', methods: ['POST'])]
    public function toggleApiAccess(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // on s'assure que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

         // On inverse la valeur actuelle de l'accès API : true devient false, et false devient true
        $user->setIsApiAccessEnabled(!$user->isApiAccessEnabled());

        $entityManager->flush();

        return $this->redirectToRoute('app_account');
    }
}
