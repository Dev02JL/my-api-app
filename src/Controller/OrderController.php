<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    #[Route('/api/orders', methods: ['GET'])]
    public function getOrders(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $repository = $em->getRepository(Order::class);

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'asc');

        $queryBuilder = $repository->createQueryBuilder('o')
            ->orderBy("o.$sortField", $sortOrder);

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $orders = $query->getResult();

        return new JsonResponse($serializer->serialize($orders, 'json'), 200, [], true);
    }

    #[Route('/api/orders/{id}', methods: ['GET'])]
    public function getOrderById(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        return new JsonResponse($serializer->serialize($order, 'json'), 200, [], true);
    }

    #[Route('/api/orders', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $order = new Order();

        foreach ($data['productIds'] as $productId) {
            $product = $em->getRepository(Product::class)->find($productId);
            if ($product) {
                $order->addProduct($product);
            }
        }

        $em->persist($order);
        $em->flush();

        return new JsonResponse($serializer->serialize($order, 'json'), 201, [], true);
    }

    #[Route('/api/orders/{orderId}/products/{productId}', methods: ['POST'])]
    public function addProductToOrder(int $orderId, int $productId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        $order->addProduct($product);

        $em->flush();

        return new JsonResponse(['message' => 'Product added to order successfully'], 200);
    }

    #[Route('/api/orders/{id}', methods: ['PUT'])]
    public function updateOrder(int $id, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $order->getProducts()->clear();

        foreach ($data['productIds'] as $productId) {
            $product = $em->getRepository(Product::class)->find($productId);
            if ($product) {
                $order->addProduct($product);
            }
        }

        $em->flush();

        return new JsonResponse($serializer->serialize($order, 'json'), 200, [], true);
    }

    #[Route('/api/orders/{id}', methods: ['DELETE'])]
    public function deleteOrder(int $id, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        $em->remove($order);
        $em->flush();

        return new JsonResponse(['message' => 'Order deleted successfully'], 200);
    }

    #[Route('/api/orders/{orderId}/products/{productId}', methods: ['DELETE'])]
    public function removeProductFromOrder(int $orderId, int $productId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        if (!$order->getProducts()->contains($product)) {
            return new JsonResponse(['message' => 'Product not found in this order'], 404);
        }

        $order->removeProduct($product);

        $em->flush();

        return new JsonResponse(['message' => 'Product removed from order successfully'], 200);
    }
}