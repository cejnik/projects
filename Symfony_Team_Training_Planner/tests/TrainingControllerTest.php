<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Training;
use App\Entity\TrainingAttendance;
use App\Repository\TrainingAttendanceRepository;

// use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TrainingControllerTest extends WebTestCase
{
    private function uniqueEmail(string $prefix): string
    {
        return $prefix.'_'.uniqid('', true).'@example.com';
    }

    public function testAnonymousUserIsRedirectedFromNewTrainingPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/trainings/new');

        $this->assertResponseRedirects('/login');
    }

    public function testRegularUserCannotAccessNewTrainingPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($this->uniqueEmail('regular_user'));
        $hashedPassword = $passwordHasher->hashPassword($user, 'Password123');
        $user->setPassword($hashedPassword);
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            'email' => $user->getEmail(),
            'password' => 'Password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $client->request('GET', '/trainings/new');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCoachCanAccessNewTrainingPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail($this->uniqueEmail('coach_user'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($user, 'Password123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_COACH']);
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            'email' => $user->getEmail(),
            'password' => 'Password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $client->request('GET', '/trainings/new');

        $this->assertResponseIsSuccessful();
    }

    public function testUserCanLeaveTraining(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $attendanceRepository = $container->get(TrainingAttendanceRepository::class);

        $coach = new User();
        $coach->setEmail($this->uniqueEmail('coach_user'));
        $hashedPassword = $passwordHasher->hashPassword($coach, 'Password123');
        $coach->setPassword($hashedPassword);
        $coach->setRoles(['ROLE_COACH']);
        $entityManager->persist($coach);
        $entityManager->flush();

        $training = new Training();
        $training->setTitle('Test Training');
        $training->setDescription('Test Description');
        $training->setScheduledAt(new \DateTimeImmutable('+1 day'));
        $training->setCreatedAt(new \DateTimeImmutable());
        $training->setUpdatedAt(new \DateTimeImmutable());
        $training->setLocation('Test Location');
        $training->setCapacity(10);
        $training->setCoach($coach);
        $entityManager->persist($training);
        $entityManager->flush();

        $participant = new User();
        $participant->setEmail($this->uniqueEmail('participant_user'));
        $hashedPassword = $passwordHasher->hashPassword($participant, 'Password123');
        $participant->setPassword($hashedPassword);
        $entityManager->persist($participant);
        $entityManager->flush();

        $attendance = new TrainingAttendance();
        $attendance->setParticipant($participant);
        $attendance->setTraining($training);
        $attendance->setJoinedAt(new \DateTimeImmutable());
        $entityManager->persist($attendance);
        $entityManager->flush();

        $client->loginUser($participant);

        $crawler = $client->request('GET', '/trainings/'.$training->getId());
        $token = $crawler->filter('form[action$="/leave"] input[name="_token"]')->attr('value');

        $client->request('POST', '/trainings/'.$training->getId().'/leave', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/trainings/'.$training->getId());
        $this->assertSame(0, $attendanceRepository->count(['participant' => $participant, 'training' => $training]));

    }

    public function testUserCanJoinTraining(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $attendanceRepository = $container->get(TrainingAttendanceRepository::class);

        $coach = new User();
        $coach->setEmail($this->uniqueEmail('coach_user'));
        $hashedPassword = $passwordHasher->hashPassword($coach, 'Password123');
        $coach->setPassword($hashedPassword);
        $coach->setRoles(['ROLE_COACH']);
        $entityManager->persist($coach);
        $entityManager->flush();

        $training = new Training();
        $training->setTitle('Test Training');
        $training->setDescription('Test Description');
        $training->setScheduledAt(new \DateTimeImmutable('+1 day'));
        $training->setCreatedAt(new \DateTimeImmutable());
        $training->setUpdatedAt(new \DateTimeImmutable());
        $training->setLocation('Test Location');
        $training->setCapacity(10);
        $training->setCoach($coach);
        $entityManager->persist($training);
        $entityManager->flush();

        $participant = new User();
        $participant->setEmail($this->uniqueEmail('participant_user'));
        $hashedPassword = $passwordHasher->hashPassword($participant, 'Password123');
        $participant->setPassword($hashedPassword);
        $entityManager->persist($participant);
        $entityManager->flush();

        $client->loginUser($participant);

        $crawler = $client->request('GET', '/trainings/'.$training->getId());
        $token = $crawler->filter('form[action$="/join"] input[name="_token"]')->attr('value');

        $client->request('POST', '/trainings/'.$training->getId().'/join', ['_token' => $token]);

        $this->assertResponseRedirects('/trainings/'.$training->getId());
        $this->assertSame(1, $attendanceRepository->count([
            'participant' => $participant,
            'training' => $training,
        ]));
    }


    // Testy padající na session
    //     1) App\Tests\TrainingControllerTest::testClientCannotJoinTrainingWhenNoCapacity
    // Symfony\Component\HttpFoundation\Exception\SessionNotFoundException: There is currently no session available.
    // public function testClientCannotJoinTrainingWhenNoCapacity(): void
    // {
    //     $client = static::createClient();
    //     $container = static::getContainer();
    //     $entityManager = $container->get(EntityManagerInterface::class);
    //     $passwordHasher = $container->get(UserPasswordHasherInterface::class);
    //     $attendanceRepository = $container->get(TrainingAttendanceRepository::class);

    //     $coach = new User();
    //     $coach->setEmail($this->uniqueEmail('coach_user'));
    //     $hashedPassword = $passwordHasher->hashPassword($coach, 'Password123');
    //     $coach->setPassword($hashedPassword);
    //     $coach->setRoles(['ROLE_COACH']);
    //     $entityManager->persist($coach);
    //     $entityManager->flush();

    //     $training = new Training();
    //     $training->setTitle('Test Training');
    //     $training->setDescription('Test Description');
    //     $training->setScheduledAt(new \DateTimeImmutable('+1 day'));
    //     $training->setCreatedAt(new \DateTimeImmutable());
    //     $training->setUpdatedAt(new \DateTimeImmutable());
    //     $training->setLocation('Test Location');
    //     $training->setCapacity(1);
    //     $training->setCoach($coach);
    //     $entityManager->persist($training);
    //     $entityManager->flush();

    //     $participant1 = new User();
    //     $participant1->setEmail($this->uniqueEmail('participant_user'));
    //     $hashedPassword = $passwordHasher->hashPassword($participant1, 'Password123');
    //     $participant1->setPassword($hashedPassword);
    //     $entityManager->persist($participant1);
    //     $entityManager->flush();

    //     $attendance = new TrainingAttendance();
    //     $attendance->setParticipant($participant1);
    //     $attendance->setTraining($training);
    //     $attendance->setJoinedAt(new \DateTimeImmutable());
    //     $entityManager->persist($attendance);
    //     $entityManager->flush();

    //     $participant2 = new User();
    //     $participant2->setEmail($this->uniqueEmail('participant_user'));
    //     $hashedPassword = $passwordHasher->hashPassword($participant2, 'Password123');
    //     $participant2->setPassword($hashedPassword);
    //     $entityManager->persist($participant2);
    //     $entityManager->flush();

    //     $client->loginUser($participant2);
    //     $client->request('GET', '/trainings/'.$training->getId());

    //     $session = $container->get('request_stack')->getSession();
    //     $session->start();

    //     $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);
    //     $token = $csrfTokenManager->getToken('join_training_'.$training->getId())->getValue();

    //     $client->request('POST', '/trainings/'.$training->getId().'/join', ['_token' => $token]);


    //     $this->assertResponseRedirects('/trainings/'.$training->getId());
    //     $this->assertSame(1, $attendanceRepository->count([
    //         'training' => $training,
    //     ]));
    //     $this->assertSame(0, $attendanceRepository->count([
    //         'training' => $training,
    //         'participant' => $participant2,
    //     ]));

    // }

    // public function testUserCannotJoinTwiceToSameTraining(): void
    // {
    //     $client = static::createClient();
    //     $container = static::getContainer();
    //     $entityManager = $container->get(EntityManagerInterface::class);
    //     $passwordHasher = $container->get(UserPasswordHasherInterface::class);
    //     $attendanceRepository = $container->get(TrainingAttendanceRepository::class);

    //     $coach = new User();
    //     $coach->setEmail($this->uniqueEmail('coach_user'));
    //     $hashedPassword = $passwordHasher->hashPassword($coach, 'Password123');
    //     $coach->setPassword($hashedPassword);
    //     $coach->setRoles(['ROLE_COACH']);
    //     $entityManager->persist($coach);
    //     $entityManager->flush();

    //     $training = new Training();
    //     $training->setTitle('Test Training');
    //     $training->setDescription('Test Description');
    //     $training->setScheduledAt(new \DateTimeImmutable('+1 day'));
    //     $training->setCreatedAt(new \DateTimeImmutable());
    //     $training->setUpdatedAt(new \DateTimeImmutable());
    //     $training->setLocation('Test Location');
    //     $training->setCapacity(5);
    //     $training->setCoach($coach);
    //     $entityManager->persist($training);
    //     $entityManager->flush();

    //     $participant = new User();
    //     $participant->setEmail($this->uniqueEmail('participant_user'));
    //     $hashedPassword = $passwordHasher->hashPassword($participant, 'Password123');
    //     $participant->setPassword($hashedPassword);
    //     $entityManager->persist($participant);
    //     $entityManager->flush();

    //     $attendance = new TrainingAttendance();
    //     $attendance->setParticipant($participant);
    //     $attendance->setTraining($training);
    //     $attendance->setJoinedAt(new \DateTimeImmutable());
    //     $entityManager->persist($attendance);
    //     $entityManager->flush();

    //     $client->loginUser($participant);

    //     $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);
    //     $token = $csrfTokenManager->getToken('join_training_'.$training->getId())->getValue();

    //     $client->request('POST', '/trainings/'.$training->getId().'/join', [
    //         '_token' => $token,
    //     ]);

    //     $this->assertResponseRedirects('/trainings/'.$training->getId());
    //     $this->assertSame(1, $attendanceRepository->count([
    //         'participant' => $participant,
    //         'training' => $training,
    //     ]));
    // }
}
