<?php

namespace App\Controller;

use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    #[Route('/mon-compte/supprimer', name: 'app_account_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();

        // Seul un utilisateur connecté peut supprimer son compte
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Protection CSRF pour sécuriser la suppression du compte
        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_account');
        }

        // Suppression des commandes et de leurs lignes associées.
        foreach ($user->getCustomerOrders() as $order) {
            foreach ($order->getOrderItems() as $orderItem) {
                $entityManager->remove($orderItem);
            }

            $entityManager->remove($order);
        }

        // Suppression des éventuels produits encore présents dans le panier
        foreach ($user->getCartItems() as $cartItem) {
            $entityManager->remove($cartItem);
        }

        // Suppression du compte utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        // Déconnexion propre de l'utilisateur après suppression du compte
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_home');
    }
}
