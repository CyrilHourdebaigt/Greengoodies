<?php

namespace App\Controller;

use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    public function index(CustomerOrderRepository $customerOrderRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $orders = $customerOrderRepository->findBy(
            ['user' => $user],
            ['validateAt' => 'ASC']
        );

        return $this->render('account/index.html.twig', [
            'orders' => $orders,
        ]);
    }
}