<?php

namespace App\Controller;

use App\Services\Firebase;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\MissingPassword;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Exception\FirebaseException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Kreait\Firebase\Auth;
use Kreait\Firebase\Database;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\RemoteConfig;
use Kreait\Firebase\Storage;
use Kreait\Firebase\DynamicLinks;
use Firebase\Auth\Token\Exception\InvalidToken;



use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class IndexController extends AbstractController
{

    /**
     * @Route("/", name="index")
     */
    public function index(Auth $auth): Response
    {
        $users = $auth->listUsers($defaultMaxResults = 1000, $defaultBatchSize = 1000);
        return $this->render('index/index.html.twig', [
            'users' => $users,
        ]);
    }

    private function form()
    {
        $form = $this->createFormBuilder(['message' => 'Type your message here'], [
            'attr' => [
                'id' => 'form',
                'method' => 'POST',
                'autocomplete' => 'off',
            ]
        ])
            ->add('displayName', TextType::class, [
                'required' => false,
                'label' => 'NOMBRE',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'NOMBRE',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido']),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'El valor es demasiado largo. Debe tener {{ limit }} carácter o menos.'
                    ])
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'required' => false,
                'label' => 'TELEFONO',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'TELEFONO',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido']),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'CORREO',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'CORREO',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido'])
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options'  => ['label' => 'CONTRASEÑA'],
                'second_options' => ['label' => 'REPITA LA CONTRASEÑA'],
            ])

            ->add('save', SubmitType::class, [
                'label' => 'GUARDAR',
                'attr' => [
                    'class' => 'btn btn-primary waves-effect waves-themed'
                ],
            ])
            ->add('clear', ResetType::class, [
                'label' => 'LIMPIAR',
                'attr' => [
                    'class' => 'btn btn-default'
                ],
            ])
            ->getForm()
        ;
        return $form;
    }

    /**
     * @Route("/new", name="index_new")
     */
    public function new(Auth $auth, Request $request): Response
    {
        $form = $this->form();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userProperties = [
                'email' => $data['email'],
                'emailVerified' => false,
                'phoneNumber' => $data['phoneNumber'],
                'password' => $data['password'],
                'displayName' => $data['displayName'],
                'disabled' => false,
            ];
            $createdUser = $auth->createUser($userProperties);
            $this->addFlash('new', 'El Registro ha sido creado exitosamente.');
            return $this->redirectToRoute('index');

        }

        return $this->render('index/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{idRegister}", name="index_edit")
     */
    public function edit($idRegister, Auth $auth, Request $request): Response
    {
        $user = $auth->getUser($idRegister);

        $form = $this->form();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userProperties = [
                'email' => $data['email'],
                'emailVerified' => false,
                'phoneNumber' => $data['phoneNumber'],
                'password' => $data['password'],
                'displayName' => $data['displayName'],
                'disabled' => false,
            ];
            $updatedUser = $auth->updateUser($idRegister, $userProperties);

            $this->addFlash('edit', 'El Registro ha sido modificado exitosamente.');
            return $this->redirectToRoute('index');
        }



        return $this->render('index/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/delete/{idRegister}", name="index_delete")
     */
    public function delete($idRegister, Auth $auth): Response
    {
        $auth->deleteUser($idRegister);
        $this->addFlash('delete', 'El Registro ha sido eliminado exitosamente.');
        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/login", name="index_login")
     */
    public function createToken(Auth $auth,Request  $request): Response
    {
        if($request->isMethod('POST')){
            try {
                $signInResult = $auth->signInWithEmailAndPassword($request->get('email'), $request->get('password'));
                dump($signInResult);
                dump('loginSatisfactorio');
            } catch (FirebaseException $e) {
                dump('loginErroneo');
            }
            exit();
        }

        return $this->render('login.html.twig', [
        ]);
    }


}
