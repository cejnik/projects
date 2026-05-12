<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reservation');

        self::assertResponseIsSuccessful();
    }

    public function testApiReservations(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/reservations');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testApiReservationsReturnsJsonArray(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/reservations');

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotFalse($json);
        $this->assertIsArray($decodedData);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testMissingReservationReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/reservations/9999');

        $this->assertResponseStatusCodeSame(404);

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertNotFalse($json);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('error', $decodedData);
    }

    public function testExistingReservation(): void
    {
        $client = static::createClient();
        $client->request('get', '/api/reservations');

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseIsSuccessful();
        $this->assertIsArray($decodedData);
        $this->assertNotEmpty($decodedData);

        $reservationId = $decodedData[0]['id'];

        $client->request('get', '/api/reservations/'.$reservationId);
        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('id', $decodedData);
        $this->assertArrayHasKey('visitorName', $decodedData);
        $this->assertArrayHasKey('visitorEmail', $decodedData);
        $this->assertArrayHasKey('startsAt', $decodedData);
        $this->assertArrayHasKey('endsAt', $decodedData);
        $this->assertArrayHasKey('note', $decodedData);
        $this->assertArrayHasKey('createdAt', $decodedData);
        $this->assertArrayHasKey('status', $decodedData);
        $this->assertArrayHasKey('room', $decodedData);
        $this->assertArrayHasKey('ownerEmail', $decodedData);
        $this->assertSame($reservationId, $decodedData['id']);
    }

    public function testCreateReservationRequiresLogin(): void
    {
        $client = static::createClient();
        $data = [
            'visitorName' => 'Test Visitor',
            'visitorEmail' => 'test@test.cz',
            'startsAt' => '2026-05-12 10:00:00',
            'endsAt' => '2026-05-12 11:00:00',
            'roomId' => 1,
            ];
        $json = json_encode($data);
        $client->request('POST', '/api/reservations', [], [], ['CONTENT_TYPE' => 'application/json'], $json);

        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(302);
    }

    public function testCreateReservationRequiresVisitorName(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $repository = $container->get(UserRepository::class);
        $user = $repository->findOneBy(['email' => 'tester@example.com']);

        $this->assertNotNull($user);
        $client->loginUser($user);
        $data = [
            'visitorEmail' => 'test@test.cz',
            'startsAt' => '2026-05-12 10:00:00',
            'endsAt' => '2026-05-12 11:00:00',
            'roomId' => 1,
        ];

        $json = json_encode($data);

        $client->request('POST', '/api/reservations', [], [], ['CONTENT_TYPE' => 'application/json'], $json);

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseStatusCodeSame(400);
        $this->assertNotFalse($json);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('error', $decodedData);
        $this->assertSame('visitorName is required.', $decodedData['error']);
    }

    public function testCreateReservationSuccessfully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $repository = $container->get(UserRepository::class);
        $user = $repository->findOneBy(['email' => 'tester@example.com']);

        $this->assertNotNull($user);
        $client->loginUser($user);

        $data = [
            'visitorName' => 'Test Visitor',
            'visitorEmail' => 'test@test.cz',
            'startsAt' => '2026-05-12 13:00:00',
            'endsAt' => '2026-05-12 14:00:00',
            'roomId' => 1,
            ];

        $json = json_encode($data);

        $client->request('POST', '/api/reservations', [], [], ['CONTENT_TYPE' => 'application/json'], $json);
        $response = $client->getResponse();
        $content = $response->getContent();
        $decodedData = json_decode($content, true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertNotFalse($content);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('id', $decodedData);
        $this->assertArrayHasKey('message', $decodedData);
        $this->assertSame('Reservation was created.', $decodedData['message']);

    }
}
