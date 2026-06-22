<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            [
                'name' => 'Savon naturel à la lavande',
                'shortDescription' => 'Un savon doux et parfumé, fabriqué à partir d’ingrédients naturels.',
                'fullDescription' => 'Ce savon naturel à la lavande nettoie la peau en douceur tout en laissant un parfum agréable et apaisant. Il est idéal pour une utilisation quotidienne et convient à tous les types de peau.',
                'price' => 4.90,
                'picture' => 'savon-lavande.jpg',
            ],
            [
                'name' => 'Shampoing solide nourrissant',
                'shortDescription' => 'Un shampoing solide écologique pour prendre soin de vos cheveux.',
                'fullDescription' => 'Ce shampoing solide nourrit les cheveux tout en limitant les emballages plastiques. Sa formule douce respecte le cuir chevelu et laisse les cheveux propres, légers et brillants.',
                'price' => 7.50,
                'picture' => 'shampoing-solide.jpg',
            ],
            [
                'name' => 'Gourde inox réutilisable',
                'shortDescription' => 'Une gourde durable pour remplacer les bouteilles jetables.',
                'fullDescription' => 'Cette gourde en inox est idéale pour transporter vos boissons au quotidien. Réutilisable, solide et facile à nettoyer, elle accompagne parfaitement une démarche zéro déchet.',
                'price' => 18.90,
                'picture' => 'gourde-inox.jpg',
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setShortDescription($productData['shortDescription']);
            $product->setFullDescription($productData['fullDescription']);
            $product->setPrice($productData['price']);
            $product->setPicture($productData['picture']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
