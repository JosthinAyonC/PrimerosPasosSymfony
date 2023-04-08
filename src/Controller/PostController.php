<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{

    private $em;

    /**
     * @param $em
     */

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    #[Route('/', name: 'app_post')]
    public function index(): Response
    {
        
        //obtener todos los posts
        $posts = $this->em->getRepository(Post::class)->findAllPosts();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }


    #[Route('/create', name: 'postCreate')]
    public function postCreate(Request $request, SluggerInterface $slugger, AuthorizationCheckerInterface $authChecker): Response{

        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si el usuario no está autenticado, redirigirlo a la página de registro
            $this->addFlash('error', 'Por favor, inicie sesión o registrese para acceder a la página de creacion de posts.');
            return $this->redirectToRoute('login');
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            //obtener archivo file
            $file = $form->get('file')->getData();

            if ($file){
                $originalFilename = pathinfo($file, PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                
                try{
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );

                }catch (FileException $e){
                    throw new \Exception('Problemas al subir tu archivo');
                }

                $post -> setFile($newFilename);
            }

            //asignacion de url referida por el Tittle
            $url = str_ireplace(" ","-",$form->get('title')->getData());
            //setear post
            $post->setUrl($url);
            
            $user = $this->getUser();
            $post->setUser($user);

            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/post.html.twig', [
            'form' => $form->createView(),
    ]);
    }

    //Detallados de cada uno
    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails(Post $post){

        return $this->render('post/post-details.html.twig', ['post' => $post]);
        
    }


    //render del eliminar usuario
    #[Route('/deletePosts', name: 'postDeleteScreen')]
    public function postDeleteScreen(){

        $posts = $this->em->getRepository(Post::class)->findAllPosts();

        return $this->render('post/post-delete/deleteScreen.html.twig', [
            'posts' => $posts,
        ]);
    }
    //elimina el usuario al hacer click
    #[Route('/delete/{id}', name: 'postDelete')]
    public function postDelete($id){

        $post = $this->em->getRepository(Post::class)->find($id);
        $this->em->remove($post);

        $this->em->flush();

        return new JsonResponse(['sucess'=> true, 'msg'=>'Datos eliminados']);
    }
}
