<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\ShipmentAddress;
use App\Form\ShipmentAddressType;
use App\Service\Invoice as ServiceInvoice;
use App\Service\PayPal;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class OrderController extends AbstractController
{
    private $serializer;
    private $session;
    private $user;

    public function __construct(EncoderInterface $encoder, NormalizerInterface $normalizer, SessionInterface $session, User $user)
    {
        $this->serializer = new Serializer([$normalizer], [$encoder]);
        $this->session = $session;
        $this->user = $user;
    }

    /**
     * @Route("/orderProcess/basket", name="orderProcess_basket")
     */
    public function basket(): Response
    {
        $identifier = $this->user->getIdentifier();

        $products = [];
        $cartPositions = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts($identifier);

        return $this->render('orderProcess/basket.html.twig', [
            'carts' => $cartPositions,
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
        $this->session->set('shipping_id', $id);

        return $this->render('orderProcess/choosePayment.html.twig', [
            'id' => $id,
        ]);
    }

    /**
    * @Route("/orderProcess/payPal", name="orderProcess_payPal")
    */
    public function payPalPayment(PayPal $payPal): Response
    {
        $client = HttpClient::create();

        $token = $payPal->getAccessToken();

        $response = new RedirectResponse($payPal->createOrder($token, 'IV-TEST', '100.00'));

        return $response;
    }

    /**
    * @Route("/orderProcess/getInvoice", name="orderProcess_getInvoice")
    */
    public function getInvoice(ServiceInvoice $invoiceService): Response
    {
        $identifier = $this->user->getIdentifier();

        $shipmentId = $this->session->get('shipping_id');
        $shipmentAddress = $this->getDoctrine()->getRepository(ShipmentAddress::class)->find($shipmentId);

        $cartPositions = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts([
            'user' => $identifier,]);
        
        $products = $invoiceService->createInvoiceProducts($cartPositions);

        $invoice = $invoiceService->create($products, $shipmentAddress);
        $invoiceNumber = $invoice->getInvoiceNumber();
        $invoicePdf = $invoiceService->createPDF($invoice);

        return new Response(
            $invoicePdf->stream($invoiceNumber, ['Attachment' => 1]),
            Response::HTTP_OK,
            ['content-Type' => 'application/pdf']
        );
    }

    /**
    * @Route("/orderProcess/complete", name="orderProcess_complete")
    */
    public function createInvoicePDF()
    {
        return $this->render('orderProcess/orderComplete.html.twig');
    }
}
