<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products', methods: ['GET'])]
    public function getProducts(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $repository = $em->getRepository(Product::class);

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'asc');

        $filters = [];
        if ($request->query->get('colour')) {
            $filters['colour'] = $request->query->get('colour');
        }
        if ($request->query->get('size')) {
            $filters['size'] = $request->query->get('size');
        }

        $queryBuilder = $repository->createQueryBuilder('p')
            ->where('1 = 1');

        foreach ($filters as $field => $value) {
            $queryBuilder->andWhere("p.$field = :$field")
                        ->setParameter($field, $value);
        }

        $queryBuilder->orderBy("p.$sortField", $sortOrder);

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $products = $query->getResult();

        return new JsonResponse($serializer->serialize($products, 'json'), 200, [], true);
    }

    #[Route('/api/products/{id}', methods: ['GET'])]
    public function getProductById(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        $json = $serializer->serialize($product, 'json');
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/products', methods: ['POST'])]
    public function createProduct(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);

        if (isset($data['size'])) {
            $product->setSize($data['size']);
        }
        if (isset($data['colour'])) {
            $product->setColour($data['colour']);
        }
        if (isset($data['quantity'])) {
            $product->setQuantity($data['quantity']);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Product created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/api/products/{id}', methods: ['PUT'])]
    public function updateProduct(int $id, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        try {
            $updatedProduct = $serializer->deserialize($request->getContent(), Product::class, 'json', ['object_to_populate' => $product]);

            $em->flush();

            return new JsonResponse($serializer->serialize($updatedProduct, 'json'), 200, [], true);
        } catch (NotEncodableValueException $e) {
            return new JsonResponse(['message' => 'Invalid data format'], 400);
        }
    }

    #[Route('/api/products/{id}', methods: ['DELETE'])]
    public function deleteProduct(int $id, EntityManagerInterface $em): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        $em->remove($product);
        $em->flush();

        return new JsonResponse(['message' => 'Product deleted successfully'], 200);
    }
}