<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $em;

    /**
     * @param $em
     */

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    #[Route('/register', name: 'userRegister')]
    public function userRegister(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $user= new User();
        $registerForm = $this->createForm(UserType::class, $user);
        $registerForm->handleRequest($request);

        if($registerForm->isSubmitted() && $registerForm->isValid()){
            //definir rol por defecto
            $user->setRoles(['ROLE_USER']);
            //encriptacion de password
            $plaintextPassword = $registerForm->get('password')->getData();

            $hashedPassword = $passwordHasher ->hashPassword(
                $user,
                $plaintextPassword
            );

            $user->setPassword($hashedPassword);

            //Guardar el usuario
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('userRegister');
        }

        return $this->render('user/index.html.twig', [
            'registerForm' => $registerForm->createView(),
        ]);
    }
}
