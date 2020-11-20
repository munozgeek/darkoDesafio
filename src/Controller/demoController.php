<?php

namespace App\Controller;

use App\Services\Firebase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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

/**
 * @Route("/demo")
 */
class demoController extends AbstractController
{
    public $database;
    public $reference;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->reference = $database->getReference('users');
    }

    /**
     * @Route("/", name="index_demo")
     */
    public function index(): Response
    {
        $snapshot = $this->reference->getSnapshot();
        $users = $snapshot->getValue();

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
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'NOMBRE',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'NOMBRE',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido']),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'El valor es demasiado largo. Debe tener {{limit}} carácter o menos.'
                    ])
                ],
            ])
            ->add('surname', TextType::class, [
                'required' => true,
                'label' => 'APELLIDO',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'APELLIDO',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido']),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'El valor es demasiado largo. Debe tener {{limit}} carácter o menos.'
                    ])
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'CORREO',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'CORREO',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Disculpe, Este campo es requerido'])
                ],
            ])
            ->add('website', TextType::class, [
                'required' => false,
                'label' => 'PAGINA WEB',
                'attr'      => [
                    'class'         => 'form-control',
                    'placeholder'   => 'PAGINA WEB',
                ]
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
     * @Route("/new", name="index_demo_new")
     */
    public function new(Request $request): Response
    {
        $form = $this->form();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->reference->push([
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'website' => $data['website'],
            ]);

            $this->addFlash('new', 'El Registro ha sido creado exitosamente.');
            return $this->redirectToRoute('index');

        }

        return $this->render('index/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{idRegister}", name="index_demo_edit")
     */
    public function edit($idRegister, Request $request): Response
    {
        $reference = $this->database->getReference('users/'.$idRegister);
        $snapshot = $reference->getSnapshot();
        $user = $snapshot->getValue();

        $form = $this->form();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $reference->set([
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'website' => $data['website'],
            ]);
            $this->addFlash('edit', 'El Registro ha sido modificado exitosamente.');
            return $this->redirectToRoute('index');
        }



        return $this->render('index/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/delete/{idRegister}", name="index_demo_delete")
     */
    public function delete($idRegister): Response
    {
        $reference = $this->database->getReference('users/'.$idRegister)->remove();
        $this->addFlash('delete', 'El Registro ha sido eliminado exitosamente.');
        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/createToken", name="index_demo_createToken")
     */
    public function createToken(Auth $auth): Response
    {
        $uid = 'some-uid';
        $additionalClaims = [
            'premiumAccount' => true
        ];

        $customToken = $auth->createCustomToken($uid, $additionalClaims);
        $customTokenString = (string) $customToken;
        dump($customTokenString);
        exit();
    }

    /**
     * @Route("/loginCheck", name="index_demo_loginCheck")
     */
    public function loginCheck(Auth $auth): Response
    {
        $idTokenString = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay10MGRiZ0BkZXNhZmlvZGFya28uaWFtLmdzZXJ2aWNlYWNjb3VudC5jb20iLCJzdWIiOiJmaXJlYmFzZS1hZG1pbnNkay10MGRiZ0BkZXNhZmlvZGFya28uaWFtLmdzZXJ2aWNlYWNjb3VudC5jb20iLCJhdWQiOiJodHRwczpcL1wvaWRlbnRpdHl0b29sa2l0Lmdvb2dsZWFwaXMuY29tXC9nb29nbGUuaWRlbnRpdHkuaWRlbnRpdHl0b29sa2l0LnYxLklkZW50aXR5VG9vbGtpdCIsImNsYWltcyI6eyJwcmVtaXVtQWNjb3VudCI6dHJ1ZX0sInVpZCI6InNvbWUtdWlkIiwiaWF0IjoxNjA1ODU1NTU4LCJleHAiOjE2MDU4NTkxNTh9.aCdOtTzZwUHTb-utzIvWv_vadalzj2KEWuV47xLMto9mfsgx_Hq5Gq9l_mkcqeE2ZqlZjZhen15RkBEGKnnbvdUQ_SwQC1pOo334UQzDTzqOnuCGmDHIwDuTGeKDs5mTBDCJ9dG6x-E2CN1aSysNn9ou0Gr2r3t3loA6Z0H4K5AchIMw3T0o7qr0oXcCG72zZfOOcAyY2bHX3TD5acv2BCZzSurdZyOV7AuuV15ckbKANei3KTltSzqxwKAOshOrvlIIER4R0wPJP9wQtyhmkbF32hozmrspmcE8SyFV6weaNkpkiq6srWfAJvgmI8C9z2GfxcAFCgpLKPtUkn8ADw';
        $signInResult = $auth->signInWithCustomToken($idTokenString);

        dump($signInResult);
        exit();
    }

    /**
     * @Route("/createUser", name="index_demo_createUser")
     */
    public function createUser(Auth $auth): Response
    {
        #$userProperties = [
        #    'email' => 'user@example.com',
        #    'emailVerified' => false,
        #   'phoneNumber' => '+15555550100',
        #    'password' => 'secretPassword',
        #    'displayName' => 'John Doe',
        #    'photoUrl' => 'http://www.example.com/12345678/photo.png',
        #    'disabled' => false,
        #];
        #$createdUser = $auth->createUser($userProperties);

        $users = $auth->listUsers($defaultMaxResults = 1000, $defaultBatchSize = 1000);
        foreach ($users as $user) {
            dump($user);
        }

        exit();

    }


}
