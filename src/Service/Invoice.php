<?php

namespace App\Service;

use App\Entity\Invoice as EntityInvoice;
use App\Entity\InvoiceProduct;
use App\Entity\ShipmentAddress;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;

class Invoice
{
    private $security;
    private $session;
    private $entityManager;
    private $serializer;
    private $twig;

    public function __construct(Security $security, SessionInterface $session, EntityManagerInterface $entityManager, NormalizerInterface $normalizer,Environment $twig)
    {
        $this->security = $security;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->serializer = new Serializer([$normalizer]);
        $this->twig = $twig;
    }

    public function create(array $products, ShipmentAddress $address)
    {
        if($user = $this->security->getUser()){
            $identifier = $user->getUuid();
        }else{
            $identifier = session_id();
        }

        $id = $this->session->get('shipping_id');

        $deliveryDate = new \DateTime();
        $deliveryDate->modify('+5 day');
        $deliveryDate = \DateTimeImmutable::createFromMutable($deliveryDate);

        $invoice = new EntityInvoice();

        foreach($products as $product){
            $invoice->addProduct($product);
        }


        $invoiceNumber = 'IV' . random_int(100000,999999);
        $invoice->setInvoiceNumber($invoiceNumber)
            ->setDeliveryAddress($address)
            ->setInvoiceDate(new \DateTimeImmutable())
            ->setDeliveryDate($deliveryDate);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    public function createPDF(EntityInvoice $invoice)
    {
        $normalizedInvoice = $this->serializer->normalize($invoice);
        $invoiceNumber = $normalizedInvoice['invoiceNumber'];

        $html = $this->twig->render('orderProcess/invoice.html.twig', $normalizedInvoice);
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $pdf = new Dompdf($pdfOptions);
        $pdf->loadHtml($html);
        $pdf->render();

        $pdfPath = '../invoices/' . $invoiceNumber . '.pdf';
        try {
            file_put_contents($pdfPath, $pdf->output());
        } catch(IOExceptionInterface $exception){
            echo "An error occured creating the Invoice with number " . $invoiceNumber . 'at ' . $exception->getPath();
        }

        return $pdf;
    }

    public function createInvoiceProducts(array $cartPositions)
    {
        $products = [];
        foreach($cartPositions as $position){
            $netPrice = $position->getProduct()->getPrice() * 100 / 119;

            $invocieProduct = new InvoiceProduct();
            $invocieProduct->setName($position->getProduct()->getName())
            ->setNetPrice($netPrice)
            ->setAmount($position->getAmount());

            $products[] = $invocieProduct;

            $this->entityManager->persist($invocieProduct);
            $this->entityManager->flush();
        }

        return $products;
    }
}