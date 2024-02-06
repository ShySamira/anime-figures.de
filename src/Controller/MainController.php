<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function home(): Response
    {
        
        return $this->render('/main/home.html.twig');
    }

    /**
     * @Route("/products", name="app_products")
     */
    public function products(): Response
    {   
        $products = $this->getDoctrine()->getRepository(Product::class)->findAllSortedByPosition();
        $session = $this->get("session");
        $session->set('activated', true);
        
        return $this->render('/ProductPage/products.html.twig', [
            'products' => $products,
        ]);
    }

    /**
    * @Route("/dashboard", name="app_dashboard")
    */
    public function dasboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('/main/dashboard.html.twig');
    }

    /**
    * @Route("/orders", name="app_orders")
    */
    public function orders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if($data = $request->getContent())
        {
            $data = explode(',', $data);
            $invoice = $this->getDoctrine()->getRepository(Invoice::class)->findOneBy([
                'invoice_number' => $data[0]
            ]);
            $invoice->setStatus($data[1]);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($invoice);
            $entityManager->flush();

            return new Response();
        }
        $invoices = $this->getDoctrine()->getRepository(Invoice::class)->findAll();

        return $this->render('/main/orders.html.twig', [
            'invoices' => $invoices
        ]);
    }

    /**
    * @Route("/order/details/{ivNumber}", name="app_order_details")
    */
    public function orderDetails($ivNumber): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $invoice = $this->getDoctrine()->getRepository(Invoice::class)->findOneBy(['invoice_number' => $ivNumber]);
        $address = $invoice->getDeliveryAddress();
        $products = $invoice->getProducts();
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'uuid' => $address->getUser()
        ]);
        return $this->render('/main/orderDetails.html.twig', [
            'invoice' => $invoice,
            'address' => $address,
            'products' => $products,
            'user' => $user,
        ]);
    }

    /**
    * @Route("/order/details/update", name="app_order_details_update")
    */
    public function orderDetailsUpdate(): Response
    {
        return new Response;
    }
}

