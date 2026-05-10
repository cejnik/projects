<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiRoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'api_rooms_index', methods: ['GET'])]
    public function index(RoomRepository $roomRepository): JsonResponse
    {
        $rooms = $roomRepository->findBy([], ['id' => 'ASC']);
        $data = [];

        foreach ($rooms as $room) {
            $data[] = [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'capacity' => $room->getCapacity(),
                'location' => $room->getLocation(),
                'isActive' => $room->isActive(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/rooms/{id}', name: 'api_room_id', methods: ['GET'])]
    public function room_id(RoomRepository $roomRepository, int $id): JsonResponse
    {
        $room = $roomRepository->find($id);
        //$data = [];

        if ($room === null) {
            $data = ['error' => 'Room is not found'];
            return $this->json($data, 404);
        }

        $data = [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'capacity' => $room->getCapacity(),
                'location' => $room->getLocation(),
                'isActive' => $room->isActive(),
        ];
        return $this->json($data);

    }

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
}
