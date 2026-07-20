<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\CustomerOrder;
use App\Entity\OrderItem;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/panier', name: 'app_cart')]
    public function index(CartItemRepository $cartItemRepository): Response
    {
        // On prépare un tableau pour le panier 
        $cartItems = [];

        // Si un utilisateur est connecté, on récupère uniquement les produits de son panier
        if ($this->getUser()) {
            $cartItems = $cartItemRepository->findBy([
                'user' => $this->getUser(),
            ]);
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // On s'assure que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // On récupère la quantité envoyée depuis la page détail produit.
        // La valeur minimale autorisée est 0, car 0 permet de retirer le produit du panier
        $quantity = max(0, (int) $request->request->get('quantity', 1));

        // On vérifie si ce produit est déjà présent dans le panier de l'utilisateur
        $cartItem = $cartItemRepository->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        // Si la quantité est à 0, le produit ne doit plus être présent dans le panier
        if ($quantity === 0) {
            if ($cartItem) {
                $entityManager->remove($cartItem);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_cart');
        }

         // Si le produit existe déjà dans le panier, on met simplement à jour sa quantité
        if ($cartItem) {
            $cartItem->setQuantity($quantity);
        } else {
            // Si le produit n'existe pas encore dans le panier, on crée une nouvelle ligne
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);

            $entityManager->persist($cartItem);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // On s'assure que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

         // On récupère toutes les lignes du panier de l'utilisateur connecté
        $cartItems = $cartItemRepository->findBy([
            'user' => $user,
        ]);

        // On supprime chaque ligne du panier
        foreach ($cartItems as $cartItem) {
            $entityManager->remove($cartItem);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/valider', name: 'app_cart_validate', methods: ['POST'])]
    public function validate(
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // On s"assure que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

         // On récupère les produits actuellement présents dans son panier
        $cartItems = $cartItemRepository->findBy([
            'user' => $user,
        ]);

        // Si le panier est vide, on ne crée pas de commande
        if (empty($cartItems)) {
            return $this->redirectToRoute('app_cart');
        }

        $total = 0;

        // On crée la commande principale, liée à l'utilisateur connecté
        $order = new CustomerOrder();
        $order->setUser($user);
        $order->setValidateAt(new \DateTimeImmutable());

         // On génère un numéro de commande unique et lisible
        $order->setOrderNumber('GG-' . date('YmdHis') . '-' . random_int(100, 999));

        // Chaque ligne du panier est transformée en ligne de commande
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();
            $lineTotal = $product->getPrice() * $quantity;

            $orderItem = new OrderItem();
            $orderItem->setCustomerOrder($order);
            $orderItem->setProductName($product->getName());
            $orderItem->setProductPrice($product->getPrice());
            $orderItem->setQuantity($quantity);
            $orderItem->setTotalPrice($lineTotal);

            // On ajoute le total de la ligne au total général de la commande
            $total += $lineTotal;

            // On enregistre la ligne de commande
            $entityManager->persist($orderItem);

            // Une fois la ligne transformée en commande, on retire le produit du panier
            $entityManager->remove($cartItem);
        }

        // On enregistre le prix total final de la commande
        $order->setTotalPrice($total);

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Merci pour votre commande ! Elle a bien été enregistrée.');

        return $this->redirectToRoute('app_cart');
    }
}
