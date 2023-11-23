<?php

namespace App\Controller;

use App\Entity\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/orderProcess/basket", name="orderProcess_basket")
     */
    public function basket(): Response
    {
        $session = $this->get("session");
        $session->set('activated', true);
        $user = session_id();
        $products = [];
        $cartPosition = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts($user);

        return $this->render('orderProcess/basket.html.twig', [
            'carts' => $cartPosition,
        ]);
    }
}
