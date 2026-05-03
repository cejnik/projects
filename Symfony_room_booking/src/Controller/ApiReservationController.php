<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Reservation;

final class ApiReservationController extends AbstractController
{
    #[Route('/api/reservations', name: 'api_reservations', methods: ['GET'])]
    public function api_reservations(ReservationRepository $reservationRepository): JsonResponse
    {
        $reservations = $reservationRepository->findBy([], ['id' => 'ASC']);
        $data = [];
        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'visitorName' => $reservation->getVisitorName(),
                'visitorEmail' => $reservation->getVisitorEmail(),
                'startsAt' => $reservation->getStartsAt()?->format('Y-m-d H:i:s'),
                'endsAt' => $reservation->getEndsAt()?->format('Y-m-d H:i:s'),
                'note' => $reservation->getNote(),
                'status' => $reservation->getStatus(),
                'createdAt' => $reservation->getCreatedAt()?->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $reservation->getRoom()->getId(),
                    'name' => $reservation->getRoom()->getName(),
                ],
                'ownerEmail' => $reservation->getUser()?->getEmail(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/reservations/{id}', name: 'api_reservation_detail', methods: ['GET'])]
    public function api_reservation_detail(int $id, ReservationRepository $reservationRepository): JsonResponse
    {
        $reservation = $reservationRepository->find($id);
        if ($reservation === null) {
            $data = [
                'error' => 'Reservation not found.'
            ];
            return $this->json($data, 404);
        }

        $data = [
            'id' => $reservation->getId(),
            'visitorName' => $reservation->getVisitorName(),
            'visitorEmail' => $reservation->getVisitorEmail(),
            'startsAt' => $reservation->getStartsAt()?->format('Y-m-d H:i:s'),
            'endsAt' => $reservation->getEndsAt()?->format('Y-m-d H:i:s'),
            'note' => $reservation->getNote(),
            'status' => $reservation->getStatus(),
            'createdAt' => $reservation->getCreatedAt()?->format('Y-m-d H:i:s'),
            'room' => [
                'id' => $reservation->getRoom()?->getId(),
                'name' => $reservation->getRoom()?->getName(),
            ],
            'ownerEmail' => $reservation->getUser()?->getEmail(),
        ];
        return $this->json($data);

    }

    #[Route('/api/my-reservations', name: 'my_reservations', methods: ['GET'])]
    public function my_reservations(ReservationRepository $reservationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $reservations = $reservationRepository->findBy(['user' => $user], ['id' => 'ASC']);
        $data = [];
        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'visitorName' => $reservation->getVisitorName(),
                'visitorEmail' => $reservation->getVisitorEmail(),
                'startsAt' => $reservation->getStartsAt()?->format('Y-m-d H:i:s'),
                'endsAt' => $reservation->getEndsAt()?->format('Y-m-d H:i:s'),
                'note' => $reservation->getNote(),
                'status' => $reservation->getStatus(),
                'createdAt' => $reservation->getCreatedAt()?->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $reservation->getRoom()?->getId(),
                    'name' => $reservation->getRoom()?->getName(),
                ],
                'ownerEmail' => $reservation->getUser()?->getEmail(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/reservations', name: 'api_reservations_post', methods: ['POST'])]
    public function api_reservations_post(Request $request, ReservationRepository $reservationRepository, RoomRepository $roomRepository, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }
        $json = $request->getContent();
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], 400);
        }
        if (empty($data['visitorName'])) {
            return $this->json(['error' => 'visitorName is required.'], 400);
        }

        if (empty($data['visitorEmail'])) {
            return $this->json(['error' => 'visitorEmail is required.'], 400);
        }

        if (empty($data['startsAt'])) {
            return $this->json(['error' => 'startsAt is required.'], 400);
        }

        if (empty($data['endsAt'])) {
            return $this->json(['error' => 'endsAt is required.'], 400);
        }

        if (empty($data['roomId'])) {
            return $this->json(['error' => 'roomId is required.'], 400);
        }

        $room = $roomRepository->find($data['roomId']);

        if ($room === null) {
            return $this->json(['error' => 'Room not found.'], 404);
        }
        try {
            $startsAt = new \DateTimeImmutable($data['startsAt']);
            $endsAt = new \DateTimeImmutable($data['endsAt']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format.'], 400);
        }
        if ($endsAt <= $startsAt) {
            return $this->json(['error' => 'End time must be later than start time.'], 400);
        }
        $conflictingReservation = $reservationRepository->findConflictingReservation($room, $startsAt, $endsAt);
        if ($conflictingReservation) {
            return $this->json(['error' => 'This room is already reserved for the selected time.'], 409);
        }
        $reservation = new Reservation();
        $reservation->setVisitorName($data['visitorName']);
        $reservation->setVisitorEmail($data['visitorEmail']);
        $reservation->setStartsAt($startsAt);
        $reservation->setEndsAt($endsAt);
        $reservation->setNote($data['note'] ?? null);
        $reservation->setStatus('confirmed');
        $reservation->setCreatedAt(new \DateTimeImmutable());
        $reservation->setRoom($room);
        $reservation->setUser($user);
        $entityManagerInterface->persist($reservation);
        $entityManagerInterface->flush();
        return $this->json([
            'message' => 'Reservation was created.',
            'id' => $reservation->getId(),
        ], 201);

    }
    #[Route('/api/reservations/{id}/cancel', name: 'api_cancel_reservation', methods: ['PATCH'])]
    public function cancelReservation(ReservationRepository $reservationRepository, EntityManagerInterface $entityManagerInterface, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $reservation = $reservationRepository->find($id);
        if ($reservation === null) {
            return $this->json(['error' => 'Reservation not found.'], 404);
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Access denied.');
        }
        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }
        if ($reservation->getStatus() === 'cancelled') {
            return $this->json(['error' => 'Reservation is already cancelled.'], 400);
        }
        $reservation->setStatus('cancelled');
        $entityManagerInterface->flush();
        return $this->json(['message' => 'Reservation was cancelled.'], 200);
    }

    #[Route('/api/reservations/{id}', name: 'api_delete_reservation', methods: ['DELETE'])]
    public function delete_reservation(ReservationRepository $reservationRepository, EntityManagerInterface $entityManagerInterface, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $reservation = $reservationRepository->find($id);
        if ($reservation === null) {
            return $this->json(['error' => 'Reservation not found.'], 404);
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
        return $this->json(['message' => 'Reservation was deleted.'], 200);
    }
}
