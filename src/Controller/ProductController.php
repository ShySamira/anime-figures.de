<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/product/add/{id}", name="product_add")
     */
    public function addProduct(int $id): Response
    {
        if($user = $this->getUser()){
            $identifier = $user->getUuid();
        }else{
            $identifier = session_id();
        }
        
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);

        if($cart = $this->getDoctrine()->getRepository(Cart::class)->findOneBy([
            'product' => $product, 
            'user' => $identifier])){
            
            $cart->increaseAmountByOne();
        }else{
            $cart = new Cart();
            $cart->setUser($identifier);
            $cart->setProduct($product);
            $cart->increaseAmountByOne();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($cart);
        $entityManager->flush();

        return $this->redirectToRoute('app_products');
    }
}
