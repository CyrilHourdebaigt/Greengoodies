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
        $cartItems = [];

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

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $cartItem = $cartItemRepository->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if ($cartItem) {
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
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

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cartItems = $cartItemRepository->findBy([
            'user' => $user,
        ]);

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

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cartItems = $cartItemRepository->findBy([
            'user' => $user,
        ]);

        if (empty($cartItems)) {
            return $this->redirectToRoute('app_cart');
        }

        $total = 0;

        $order = new CustomerOrder();
        $order->setUser($user);
        $order->setValidateAt(new \DateTimeImmutable());
        $order->setOrderNumber('GG-' . date('YmdHis') . '-' . random_int(100, 999));

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

            $total += $lineTotal;

            $entityManager->persist($orderItem);
            $entityManager->remove($cartItem);
        }

        $order->setTotalPrice($total);

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }
}
