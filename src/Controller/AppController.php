<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AppController extends AbstractController
{
    #[Route('/app/mes-rdv', name: 'app_my_appointments', methods: ['GET'])]
    public function myAppointments(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('app/placeholder.html.twig', [
            'user' => $user,
            'title' => 'Mes rendez-vous',
        ]);
    }
}
