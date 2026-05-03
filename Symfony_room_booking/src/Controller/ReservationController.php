<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use App\Entity\User;
use App\Repository\RoomRepository;

final class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'index_reservation')]
    public function index(ReservationRepository $reservationRepository, Request $request, RoomRepository $roomRepository): Response
    {
        $rooms = $roomRepository->findBy([], ['id' => 'ASC']);
        $roomId = $request->query->get('room');

        if ($roomId) {
            $selectedRoom = $roomRepository->find($roomId);
        } else {
            $selectedRoom = null;
        }
        if ($selectedRoom) {
            $status_confirmed = $reservationRepository->findBy(['status' => 'confirmed', 'room' => $selectedRoom], ['id' => 'ASC']);
            $status_cancelled = $reservationRepository->findBy(['status' => 'cancelled', 'room' => $selectedRoom], ['id' => 'ASC']);
        } else {
            $status_confirmed = $reservationRepository->findBy(['status' => 'confirmed'], ['id' => 'ASC']);
            $status_cancelled = $reservationRepository->findBy(['status' => 'cancelled'], ['id' => 'ASC']);
        }
        return $this->render('reservation/index.html.twig', [
            'activeReservations' => $status_confirmed,
            'cancelledReservations' => $status_cancelled,
            'rooms' => $rooms,
            'selectedRoomId' => $roomId
        ]);
    }

    #[Route('/reservation/new', name: 'new_reservation')]
    public function new_reservation(Request $request, EntityManagerInterface $entityManagerInterface, ReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $startsAt = $reservation->getStartsAt();
            $endsAt = $reservation->getEndsAt();
            $room = $reservation->getRoom();

            if ($startsAt && $endsAt && $room) {

                if ($endsAt <= $startsAt) {
                    $form->get('endsAt')->addError(new FormError('End time must be later than start time.'));
                } else {
                    $conflictingReservation = $reservationRepository->findConflictingReservation($room, $startsAt, $endsAt);
                    if ($conflictingReservation) {
                        $form->get('endsAt')->addError(new FormError('This room is already reserved for the selected time.'));
                    }
                }
            }

            if ($form->isValid()) {
                $reservation->setStatus('confirmed');
                $reservation->setCreatedAt(new \DateTimeImmutable());
                $user = $this->getUser();
                if (!$user instanceof User) {
                    throw $this->createAccessDeniedException('Access denied.');
                }
                $reservation->setUser($user);
                $entityManagerInterface->persist($reservation);
                $entityManagerInterface->flush();
                $this->addFlash('success', 'Reservation was created.');
                return $this->redirectToRoute('index_reservation');
            }
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/reservation/edit/{id}', name: 'edit_reservation')]
    public function edit_reservation(ReservationRepository $reservationRepository, int $id, Request $request, EntityManagerInterface $entityManagerInterface): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return $this->redirectToRoute('index_reservation');
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $startsAt = $reservation->getStartsAt();
            $endsAt = $reservation->getEndsAt();
            $room = $reservation->getRoom();

            if ($startsAt && $endsAt && $room) {
                if ($endsAt <= $startsAt) {
                    $form->get('endsAt')->addError(new FormError('End time must be later than start time.'));
                } else {
                    $conflictingReservation = $reservationRepository->findConflictingReservation($room, $startsAt, $endsAt, $reservation->getId());
                    if ($conflictingReservation) {
                        $form->get('endsAt')->addError(new FormError('This room is already reserved for the selected time.'));
                    }
                }
            }
            if ($form->isValid()) {
                $entityManagerInterface->flush();
                $this->addFlash('success', 'Reservation was updated.');
                return $this->redirectToRoute('index_reservation');
            }

        }
        return $this->render('reservation/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/reservation/delete/{id}', name: 'delete_reservation')]
    public function delete_reservation(int $id, Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManagerInterface): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->redirectToRoute('index_reservation');
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_reservation_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('index_reservation');
        }

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return $this->redirectToRoute('index_reservation');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $entityManagerInterface->remove($reservation);
        $entityManagerInterface->flush();
        $this->addFlash('success', 'Reservation was deleted.');

        return $this->redirectToRoute('index_reservation');
    }

    #[Route('/reservation/cancel/{id}', name: 'cancel_reservation')]
    public function action(int $id, Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManagerInterface): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->redirectToRoute('index_reservation');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cancel_reservation_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('index_reservation');
        }

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return $this->redirectToRoute('index_reservation');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }


        if ($reservation->getStatus() === 'cancelled') {
            return $this->redirectToRoute('index_reservation');
        }

        $reservation->setStatus('cancelled');
        $entityManagerInterface->flush();
        $this->addFlash('success', 'Reservation was cancelled.');
        return $this->redirectToRoute('index_reservation');
    }
}
