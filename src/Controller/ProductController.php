<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Categories;
use App\Entity\Category;
use App\Entity\Product;
use App\Form\EditProductType;
use App\Form\NewProductType;
use App\Service\FileUploader;
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
        $blockedSlugs = [
            'new',
            'delete',
            'details',
            'edit',
            'category',
        ];

        $form = $this->createForm(NewProductType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $product = $form->getData();
            $productName = $product->getName();

            if($form->get('submitDraft')->isClicked())
            {
                $product->setState('draft');
            }else
            {
                $product->setState('live');
            }

            if(in_array($productName, $blockedSlugs))
            {
                $this->addFlash(
                    'error',
                    'The generated Slug for the product name "' . $productName . '" is not available!',
                );
                return $this->render('/productPage/newProduct.html.twig',[
                    'form' => $form->createView(),
                ]);
            }
            if($this->getDoctrine()->getRepository(Product::class)->findOneBy(['name' => $productName]))
            {
                $this->addFlash(
                    'error',
                    'Could not save Product, the name already exists!'
                );
                return $this->render('/productPage/newProduct.html.twig',[
                    'form' => $form->createView(),
                ]);
            }
            $slug = $productName;
            strtolower($slug);
            str_replace(' ', '-', $slug);
            str_replace('.', '_', $slug);

            $product->setSlug($slug);
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Product was saved succesfull!'
            );

            return $this->redirectToRoute('app_products');
        }

        return $this->render('/productPage/newProduct.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    /**
    * @Route("/product/edit/{id}", name="product_edit")
    */
    public function editProduct(int $id, Request $request, FileUploader $fileUploader): Response
    {
        $blockedSlugs = [
            'new',
            'delete',
            'details',
            'edit',
            'category',
        ];
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
        $form = $this->createForm(EditProductType::class, $product);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $editedProduct = $form->getData();

            $pictureFile = $form->get('picture')->getData();
            if($pictureFile)
            {
                $pictureFilename = $fileUploader->upload($pictureFile);
                $product->setPictureFilename($pictureFilename);
            }
            
            $productName = $editedProduct->getName();

            if(in_array($productName, $blockedSlugs))
            {
                $this->addFlash(
                    'error',
                    'The generated Slug for the product name "' . $productName . '" is not available!',
                );
                return $this->render('/productPage/editProduct.html.twig',[
                    'form' => $form->createView(),
                ]);
            }
            if($existingProduct = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['name' => $productName]))
            {
                if($existingProduct->getId() != $id)
                {
                    $this->addFlash(
                        'error',
                        'Could not save Product, the name already exists for a different product!'
                    );
                    return $this->render('/productPage/newProduct.html.twig',[
                        'form' => $form->createView(),
                    ]);
                }
            }
            $slug = $productName;
            strtolower($slug);
            str_replace(' ', '-', $slug);
            str_replace('.', '_', $slug);

            $editedProduct->setSlug($slug);

            
            $entityManager->persist($editedProduct);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Product was saved succesfull!'
            );

            return $this->redirectToRoute('app_products');
        }

        return $this->render('/productPage/editProduct.html.twig',[
            'form' => $form->createView(),
            'product' => $product,
        ]);
    
    }

    /**
    * @Route("/product/delete/{id}", name="product_delete")
    */
    public function delete(int $id): Response
    {
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        if($carts = $this->getDoctrine()->getRepository(Cart::class)->findBy(['product' => $product]))
        {
            foreach($carts as $cart)
            {
                $entityManager->remove($cart);
            }

        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash(
            'notice',
            'Product was deleted succesfull!'
        );

        return $this->redirectToRoute('app_products');
    }

    /**
    * @Route("/product/category/new", name="product_category_new")
    */
    public function newCategory(Request $request): Response
    {   
        $entityManager = $this->getDoctrine()->getManager();

        $data = explode(',', $request->getContent());

        $category = new Category();
        $category->setLabel($data[0]);

        if($data[0] == 'PARENT' || $data[1] == 'PARENT')
        {
            $this->addFlash(
                'notice',
                'The entered category label is not valid!'
            );

            return new Response('', 401);
        }

        if($data[1] != null)
        {
            if($parent = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['label' => $data[1]]))
            {
                $category->setParentLabel($parent->getLabel());
            }
            else
            {
                $parentCategory = new Category();
                $parentCategory->setLabel($data[1]);
                $parentCategory->setParentLabel('PARENT');

                $category->setParentLabel($data[1]);

                $entityManager->persist($parentCategory);
            }
        }

        $entityManager->persist($category);

        $entityManager->flush();
        
        $this->addFlash(
            'notice',
            'The new category "'. $data[0] . '" was saved succesfull!'
        );

        return new Response();
    }


}