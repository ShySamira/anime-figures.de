<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Invoice;
use App\Entity\InvoiceProduct;
use App\Entity\ShipmentAddress;
use App\Form\ShipmentAddressType;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Date;

class OrderController extends AbstractController
{
    /**
     * @Route("/orderProcess/basket", name="orderProcess_basket")
     */
    public function basket(): Response
    {
        if($user = $this->getUser()){
            $identifier = $user->getUuid();
        }else{
            // $session = $this->get("session");
            // $session->set('activated', true);
            $identifier = session_id();
        }

        $products = [];
        $cartPosition = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts($identifier);

        return $this->render('orderProcess/basket.html.twig', [
            'carts' => $cartPosition,
        ]);
    }

    /**
     * @Route("/orderProcess/shipment", name="orderProcess_shipment")
     */
    public function shipment(Request $request): Response
    {
        $uniqueUID = $this->getUser()->getUuid();


        $address = new ShipmentAddress();

        $form = $this->createForm(ShipmentAddressType::class, $address);
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $address = $form->getData();

            if($uniqueUID){
                $address->setUser($uniqueUID);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($address);
            $entityManager->flush();

            return $this->redirectToRoute('orderProcess_shipment');
        }

        if($uniqueUID){
            $availableAddresses = $this->getDoctrine()->getRepository(ShipmentAddress::class)->findAll([
                'User' => $uniqueUID,
            ]);
        }

        return $this->render('orderProcess/shipment.html.twig', [
            'form' => $form->createView(),
            'availableAddresses' => $availableAddresses,
        ]);
    }

    /**
    * @Route("/orderProcess/choosePayment/{id}", name="orderProcess_choosePayment")
    */
    public function choosePayment($id): Response
    {
        

        return $this->render('orderProcess/choosePayment.html.twig', [
            'id' => $id,
        ]);
    }

    /**
    * @Route("/orderProcess/invoice/{id}", name="orderProcess_invoice")
    */
    public function invoice($id): Response
    {
        if($user = $this->getUser()){
            $identifier = $user->getUuid();
        }else{
            $identifier = session_id();
        }

        $entityManager = $this->getDoctrine()->getManager();

        $address = $this->getDoctrine()->getRepository(ShipmentAddress::class)->find($id);
        $cartPosition = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts([
            'user' => $identifier,
        ]);
        $deliveryDate = new \DateTime();
        $deliveryDate->modify('+5 day');
        $deliveryDate = \DateTimeImmutable::createFromMutable($deliveryDate);

        $invoice = new Invoice();
        foreach($cartPosition as $position){
            $netPrice = $position->getProduct()->getPrice() * 100 / 119;
            
            $invocieProduct = new InvoiceProduct;
            $invocieProduct->setName($position->getProduct()->getName())
                ->setNetPrice($netPrice)
                ->setAmount($position->getAmount());
            
            $invoice->addProduct($invocieProduct);
            $entityManager->persist($invocieProduct);
        }



        $invoice->setInvoiceNumber('IV' . random_int(100000,999999))
            ->setDeliveryAddress($address)
            ->setInvoiceDate(new \DateTimeImmutable())
            ->setDeliveryDate($deliveryDate);

        $entityManager->persist($invoice);
        $entityManager->flush();

        return $this->render('orderProcess/invoice.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}
