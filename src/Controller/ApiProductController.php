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
        // On récupère donc l'utilisateur authentifié grâce au token envoyé dans la requête
        $user = $this->getUser();

        // On vérifie que l'utilisateur a toujours activé son accès API
        if (!$user || !$user->isApiAccessEnabled()) {
            return new JsonResponse([
                'message' => 'Accès API non activé.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // On récupère tous les produits présents en base
        $products = $productRepository->findAll();

        // On prépare un tableau pour contrôler les données renvoyées dans la réponse JSON.
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

        // La route renvoie un tableau de produits
        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
