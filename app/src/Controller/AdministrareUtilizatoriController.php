<?php

namespace App\Controller;

use App\Entity\Utilizator;
use App\Form\EditareUtilizatorType;
use App\Form\UtilizatorNouType;
use App\Repository\UtilizatorRepository;
use App\Security\GeneratorParolaInitiala;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdministrareUtilizatoriController extends AbstractController
{
    #[Route('/administrare/utilizatori', name: 'administrare_utilizatori', methods: ['GET'])]
    public function listeaza(UtilizatorRepository $utilizatorRepository): Response
    {
        return $this->render('administrare/utilizatori.html.twig', [
            'utilizatori' => $utilizatorRepository->gasestePentruAdministrare(),
        ]);
    }

    #[Route('/administrare/utilizatori/nou', name: 'administrare_utilizator_nou', methods: ['GET', 'POST'])]
    public function creeaza(
        Request $request,
        EntityManagerInterface $managerEntitati,
        GeneratorParolaInitiala $generatorParola,
        UserPasswordHasherInterface $hasherParola,
    ): Response {
        $parolaGenerata = $request->getSession()->remove('parola_utilizator_nou');
        if (is_string($parolaGenerata)) {
            $raspuns = $this->render('administrare/parola_utilizator_nou.html.twig', [
                'parola' => $parolaGenerata,
            ]);
            $raspuns->headers->set('Cache-Control', 'no-store, private');

            return $raspuns;
        }

        $utilizator = new Utilizator();
        $formular = $this->createForm(UtilizatorNouType::class, $utilizator, [
            'action' => $this->generateUrl('administrare_utilizator_nou'),
        ]);
        $formular->handleRequest($request);

        if ($formular->isSubmitted() && $formular->isValid()) {
            $esteAdministrator = true === $formular->get('administrator')->getData();
            $utilizator
                ->setRoluri($esteAdministrator ? ['ROLE_ADMIN'] : [])
                ->setActiv(true);

            $parola = $generatorParola->genereaza();
            $utilizator->setParola($hasherParola->hashPassword($utilizator, $parola));
            $managerEntitati->persist($utilizator);

            try {
                $managerEntitati->flush();
            } catch (UniqueConstraintViolationException) {
                unset($parola);
                $formular->get('email')->addError(new FormError('Există deja un utilizator cu această adresă de email.'));

                return $this->render('administrare/utilizator_nou.html.twig', [
                    'formular' => $formular,
                ]);
            }

            $request->getSession()->set('parola_utilizator_nou', $parola);
            unset($parola);

            return $this->redirectToRoute('administrare_utilizator_nou');
        }

        return $this->render('administrare/utilizator_nou.html.twig', [
            'formular' => $formular,
        ]);
    }

    #[Route('/administrare/utilizatori/{id}/editeaza', name: 'administrare_utilizator_editeaza', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function editeaza(
        Utilizator $utilizator,
        Request $request,
        EntityManagerInterface $managerEntitati,
    ): Response {
        $formular = $this->createForm(EditareUtilizatorType::class, $utilizator, [
            'action' => $this->generateUrl('administrare_utilizator_editeaza', ['id' => $utilizator->getId()]),
            'administrator' => in_array('ROLE_ADMIN', $utilizator->getRoles(), true),
            'activ' => $utilizator->esteActiv(),
        ]);
        $formular->handleRequest($request);

        if ($formular->isSubmitted()) {
            $utilizatorAutentificat = $this->getUser();
            $estePropriulCont = $utilizatorAutentificat instanceof Utilizator
                && $utilizatorAutentificat->getId() === $utilizator->getId();
            $esteAdministrator = true === $formular->get('administrator')->getData();
            $esteActiv = true === $formular->get('activ')->getData();

            if ($estePropriulCont && !$esteAdministrator) {
                $formular->get('administrator')->addError(new FormError('Nu vă puteți elimina propriul rol de administrator.'));
            }

            if ($estePropriulCont && !$esteActiv) {
                $formular->get('activ')->addError(new FormError('Nu vă puteți dezactiva propriul cont.'));
            }

            if ($formular->isValid()) {
                $utilizator
                    ->setRoluri($esteAdministrator ? ['ROLE_ADMIN'] : [])
                    ->setActiv($esteActiv);

                try {
                    $managerEntitati->flush();
                } catch (UniqueConstraintViolationException) {
                    $formular->get('email')->addError(new FormError('Există deja un utilizator cu această adresă de email.'));

                    return $this->render('administrare/utilizator_editeaza.html.twig', [
                        'formular' => $formular,
                        'utilizator' => $utilizator,
                    ]);
                }

                $this->addFlash('success', 'Utilizatorul a fost actualizat.');

                return $this->redirectToRoute('administrare_utilizatori');
            }
        }

        return $this->render('administrare/utilizator_editeaza.html.twig', [
            'formular' => $formular,
            'utilizator' => $utilizator,
        ]);
    }
}
