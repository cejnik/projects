<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    /*
     * Rozpracovane testy pro join/leave flow byly docasne vypnuty.
     * Narazily na auth/CSRF test setup v Symfony test klientovi.
     */

    /*

    public function testClientCannotJoinTrainingWhenNoCapacity(): void
    {
        //Data
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);


        $coach = new User();
        $coach->setEmail($this->uniqueEmail('capacity_coach'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
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
        $training->setCapacity(1);
        $training->setCoach($coach);
        $entityManager->persist($training);
        $entityManager->flush();


        $partifipant1 = new User();
        $partifipant1->setEmail($this->uniqueEmail('capacity_participant_1'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($partifipant1, 'Password123');
        $partifipant1->setPassword($hashedPassword);
        $entityManager->persist($partifipant1);
        $entityManager->flush();

        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setTraining($training);
        $trainingAttendance->setParticipant($partifipant1);
        $trainingAttendance->setjoinedAt(new \DateTimeImmutable());
        $entityManager->persist($trainingAttendance);
        $entityManager->flush();

        $participant2 = new User();
        $participant2->setEmail($this->uniqueEmail('capacity_participant_2'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($participant2, 'Password123');
        $participant2->setPassword($hashedPassword);
        $entityManager->persist($participant2);
        $entityManager->flush();

        //Action
        $crawler = $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            'email' => $participant2->getEmail(),
            'password' => 'Password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $token = $csrfTokenManager->getToken('join_training_'.$training->getId())->getValue();

        $client->request('POST', '/trainings/'.$training->getId().'/join', ['_token' => $token,]);

        //Response
        $this->assertResponseRedirects('/trainings/' . $training->getId());
        $this->assertCount(1, $training->getAttendances());

    }

    public function testUserCannotJoinTwiceToSameTraining(): void
    {
        //Data
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

        $coach = new User();
        $coach->setEmail($this->uniqueEmail('duplicate_join_coach'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
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
        $training->setCapacity(5);
        $training->setCoach($coach);
        $entityManager->persist($training);
        $entityManager->flush();

        $partifipant1 = new User();
        $partifipant1->setEmail($this->uniqueEmail('duplicate_join_participant'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($partifipant1, 'Password123');
        $partifipant1->setPassword($hashedPassword);
        $entityManager->persist($partifipant1);
        $entityManager->flush();

        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setTraining($training);
        $trainingAttendance->setParticipant($partifipant1);
        $trainingAttendance->setjoinedAt(new \DateTimeImmutable());
        $entityManager->persist($trainingAttendance);
        $entityManager->flush();

        //Action
        $crawler = $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            'email' => $partifipant1->getEmail(),
            'password' => 'Password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $token = $csrfTokenManager->getToken('join_training_'.$training->getId())->getValue();

        $client->request('POST', '/trainings/'.$training->getId().'/join', ['_token' => $token,]);


        //Response
        $this->assertResponseRedirects('/trainings/' . $training->getId());
        $this->assertCount(1, $training->getAttendances());
    }

    public function testUserCanLeaveTraining(): void
    {
        //Data
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $coach = new User();
        $coach->setEmail($this->uniqueEmail('leave_coach'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
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
        $training->setCapacity(5);
        $training->setCoach($coach);
        $entityManager->persist($training);
        $entityManager->flush();


        $partifipant1 = new User();
        $partifipant1->setEmail($this->uniqueEmail('leave_participant'));
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($partifipant1, 'Password123');
        $partifipant1->setPassword($hashedPassword);
        $entityManager->persist($partifipant1);
        $entityManager->flush();

        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setTraining($training);
        $trainingAttendance->setParticipant($partifipant1);
        $trainingAttendance->setjoinedAt(new \DateTimeImmutable());
        $entityManager->persist($trainingAttendance);
        $entityManager->flush();

        //Action
        $crawler = $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            'email' => $partifipant1->getEmail(),
            'password' => 'Password123',
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);

        $crawler = $client->request('GET', '/trainings/'.$training->getId());
        $token = $crawler
            ->filter('form[action$="/leave"] input[name="_token"]')
            ->attr('value');

        $client->request('POST', '/trainings/'.$training->getId().'/leave', ['_token' => $token,]);

        //Response
        $this->assertResponseRedirects('/trainings/' . $training->getId());
        $this->assertCount(0, $training->getAttendances());
    }
    */

}
