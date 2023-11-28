<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Invoice;
use App\Entity\InvoiceProduct;
use App\Entity\ShipmentAddress;
use App\Form\ShipmentAddressType;
use DateTime;
use DateTimeImmutable;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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

        if(!$cartPositions = $this->getDoctrine()->getRepository(Cart::class)->findAllWithProducts([
            'user' => $identifier,
        ])){
            return $this->redirectToRoute('orderProcess_complete');
        }
        $address = $this->getDoctrine()->getRepository(ShipmentAddress::class)->find($id);

        $deliveryDate = new \DateTime();
        $deliveryDate->modify('+5 day');
        $deliveryDate = \DateTimeImmutable::createFromMutable($deliveryDate);

        $invoice = new Invoice();
        foreach($cartPositions as $position){
            $netPrice = $position->getProduct()->getPrice() * 100 / 119;
            
            $invocieProduct = new InvoiceProduct;
            $invocieProduct->setName($position->getProduct()->getName())
                ->setNetPrice($netPrice)
                ->setAmount($position->getAmount());
            
            $invoice->addProduct($invocieProduct);
            $entityManager->persist($invocieProduct);
            $entityManager->remove($position);
        }


        $invoiceNumber = 'IV' . random_int(100000,999999);
        $invoice->setInvoiceNumber($invoiceNumber)
            ->setDeliveryAddress($address)
            ->setInvoiceDate(new \DateTimeImmutable())
            ->setDeliveryDate($deliveryDate);

        $entityManager->persist($invoice);
        $entityManager->flush();

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $normalizedInvoice = $serializer->normalize($invoice);

        $html = $this->renderView('orderProcess/invoice.html.twig', $normalizedInvoice);
        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->render();

        $pdfPath = '../invoices/' . $invoiceNumber . '.pdf';
        try {
            file_put_contents($pdfPath, $pdf->output());
        } catch(IOExceptionInterface $exception){
            echo "An error occured creating the Invoice with number " . $invoiceNumber . 'at ' . $exception->getPath();
        }
        


        return new Response(
            $pdf->stream($invoiceNumber, ['Attachment' => 1]),
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
