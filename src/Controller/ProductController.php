<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CartItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/produit/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product, CartItemRepository $cartItemRepository): Response
    {
        $cartItem = null;

        // Si un utilisateur est connecté, on vérifie si ce produit existe déjà dans son panier
        // Cela permet d'afficher la quantité actuelle sur la page détail produit
        if ($this->getUser()) {
            $cartItem = $cartItemRepository->findOneBy([
                'user' => $this->getUser(),
                'product' => $product,
            ]);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'cartItem' => $cartItem,
        ]);
    }
}