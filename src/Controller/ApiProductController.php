<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiProductController extends AbstractController
{
    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user->isApiAccessEnabled()) {
            return new JsonResponse([
                'message' => 'Accès API non activé.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $products = $productRepository->findAll();

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'shortDescription' => $product->getShortDescription(),
                'fullDescription' => $product->getFullDescription(),
                'price' => $product->getPrice(),
                'picture' => $product->getPicture(),
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
