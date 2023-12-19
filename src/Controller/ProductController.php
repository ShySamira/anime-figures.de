<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Form\NewProductType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{   

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
    * @Route("/product/details/{productSlug}", name="details")
    */
    public function details($productSlug): Response
    {
        $this->logger->info('details site was called', ['target' => $productSlug]);
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['slug' => $productSlug]);

        if(!$product){
            $this->logger->error('Could not find details for product', ['slug' => $productSlug]);
            return $this->redirectToRoute('app_home');
        }
        return $this->render('ProductPage/details.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/product/add/{id}", name="product_add")
     */
    public function addProduct(int $id): Response
    {
        $this->logger->info('addProduct site was called', ['slug' => $id]);
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

        $this->logger->info('product was added to basket', [
            'product' => $product->getName(),
            'user' => $identifier]);
        return $this->redirectToRoute('app_products');
    }

    /**
    * @Route("/product/new", name="product_new")
    */
    public function newProduct(Request $request): Response
    {
        $product = new Product();

        $form = $this->createForm(NewProductType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $product = $form->getData();
            $slug = $product->getName();
            strtolower($slug);
            str_replace(' ', '-', $slug);
            str_replace('.', '_', $slug);

            $product->setSlug($slug);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_products');
        }

        return $this->render('/productPage/newProduct.html.twig',[
            'form' => $form->createView(),
        ]);
    }
}
