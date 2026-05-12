<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RoomControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testApiRooms(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rooms');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testIndexDisplayRoomList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Room');
    }

    public function testApiRoomsReturnsJsonArray(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rooms');

        //Data
        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertNotFalse($json); //$json muže vrátit string|false
        $this->assertIsArray($decodedData);
    }

    public function testExistingRoom(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rooms');

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseIsSuccessful();
        $this->assertIsArray($decodedData);
        $this->assertNotEmpty($decodedData);

        $roomId = $decodedData[0]['id'];

        $client->request('GET', '/api/rooms/' . $roomId);

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('id', $decodedData);
        $this->assertArrayHasKey('name', $decodedData);
        $this->assertArrayHasKey('capacity', $decodedData);
        $this->assertArrayHasKey('location', $decodedData);
        $this->assertArrayHasKey('isActive', $decodedData);
        $this->assertSame($roomId, $decodedData['id']);
    }

    public function testMissingRoomReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rooms/9999');

        $this->assertResponseStatusCodeSame(404);

        $response = $client->getResponse();
        $json = $response->getContent();
        $decodedData = json_decode($json, true);

        $this->assertNotFalse($json);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('error', $decodedData);
    }
}
