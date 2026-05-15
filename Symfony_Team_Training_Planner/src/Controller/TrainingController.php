<?php

namespace App\Controller;

use App\Entity\Training;
use App\Entity\TrainingAttendance;
use App\Form\TrainingType;
use App\Repository\TrainingAttendanceRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrainingController extends AbstractController
{
    #[Route('/trainings', name: 'app_training_index')]
    public function index(TrainingRepository $trainingRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $trainings = $trainingRepository->findBy([], ['id' => 'ASC']);

        $trainingCards = [];

        foreach ($trainings as $training) {
            $attendanceCount = $training->getAttendances()->count();
            $remainingSpots = $training->getCapacity() - $attendanceCount;

            $trainingCards[] = [
                'training' => $training,
                'attendanceCount' => $attendanceCount,
                'remainingSpots' => $remainingSpots,
            ];
        }

        return $this->render('training/index.html.twig', [
            'trainingCards' => $trainingCards,
        ]);
    }

    #[Route('/trainings/new', name: 'app_training_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');

        $training = new Training();
        $form = $this->createForm(TrainingType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $training->setCoach($this->getUser());
            $training->setCreatedAt(new \DateTimeImmutable());
            $training->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($training);
            $entityManager->flush();

            $this->addFlash('success', 'Training created successfully!');

            return $this->redirectToRoute('app_training_index');
        }

        if ($form->isSubmitted()) {
            return $this->render('training/new.html.twig', [
                'form' => $form->createView(),
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('training/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/trainings/{id}', name: 'app_training_detail')]
    public function detail(TrainingRepository $trainingRepository, int $id, TrainingAttendanceRepository $trainingAttendanceRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $training = $trainingRepository->find($id);

        if ($training === null) {
            throw $this->createNotFoundException('Training not found');
        }

        $attendance = $trainingAttendanceRepository->findOneBy(['participant' => $this->getUser(), 'training' => $training]);
        $isJoined = $attendance !== null;

        $attendanceCount = $training->getAttendances()->count();
        $remainingSpots = $training->getCapacity() - $attendanceCount;

        return $this->render('training/training_detail.html.twig', [
            'training' => $training,
            'isJoined' => $isJoined,
            'attendanceCount' => $attendanceCount,
            'remainingSpots' => $remainingSpots,
        ]);
    }

    #[Route('/trainings/{id}/edit', name: 'app_training_edit')]
    public function edit(TrainingRepository $trainingRepository, int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');

        $training = $trainingRepository->find($id);
        if ($training === null) {
            throw $this->createNotFoundException('Training not found');
        }

        $user = $this->getUser();
        if ($user !== $training->getCoach()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $form = $this->createForm(TrainingType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $training->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Training updated successfully!');

            return $this->redirectToRoute('app_training_index');
        }

        if ($form->isSubmitted()) {
            return $this->render('training/edit.html.twig', [
                'form' => $form->createView(),
            ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('training/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/trainings/{id}/delete', name: 'app_training_delete', methods: ['POST'])]
    public function delete(Request $request, TrainingRepository $trainingRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_training_'.$id, $token)) {
            return $this->redirectToRoute('app_training_index');
        }

        $training = $trainingRepository->find($id);
        if ($training === null) {
            throw $this->createNotFoundException('Training not found');
        }

        $user = $this->getUser();
        if ($user !== $training->getCoach()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $entityManager->remove($training);
        $entityManager->flush();
        $this->addFlash('success', 'Training deleted successfully!');
        return $this->redirectToRoute('app_training_index');
    }

    #[Route('/trainings/{id}/join', name: 'app_training_join', methods: ['POST'])]
    public function join(Request $request, TrainingRepository $trainingRepository, EntityManagerInterface $entityManager, int $id, TrainingAttendanceRepository $trainingAttendanceRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $training = $trainingRepository->find($id);

        if ($training === null) {
            throw $this->createNotFoundException('Training not found.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('join_training_'.$id, $token)) {
            return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
        }

        $user = $this->getUser();
        $existingAttendance = $trainingAttendanceRepository->findOneBy(['participant' => $user, 'training' => $training]);

        $attendanceCount = $training->getAttendances()->count();
        if ($attendanceCount >= $training->getCapacity()) {
            $this->addFlash('warning', 'Cannot join training. Capacity has been reached.');
            return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
        }

        if ($existingAttendance === null) {
            $trainingAttendance = new TrainingAttendance();
            $trainingAttendance->setParticipant($user);
            $trainingAttendance->setTraining($training);
            $trainingAttendance->setJoinedAt(new \DateTimeImmutable());
            $entityManager->persist($trainingAttendance);
            $entityManager->flush();
            $this->addFlash('success', 'You have joined the training.');
            return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
        }
        $this->addFlash('info', 'You are already joined to this training.');
        return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
    }

    #[Route('/trainings/{id}/leave', name: 'app_training_leave', methods: ['POST'])]
    public function leave(Request $request, int $id, TrainingRepository $trainingRepository, TrainingAttendanceRepository $trainingAttendanceRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $training = $trainingRepository->find($id);

        if ($training === null) {
            throw $this->createNotFoundException('Training not found.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('leave_training_'.$id, $token)) {
            return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
        }

        $user = $this->getUser();
        $attendance = $trainingAttendanceRepository->findOneBy(['participant' => $user, 'training' => $training]);
        if ($attendance === null) {
            $this->addFlash('info', 'You are not joined to this training.');
            return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
        }
        $entityManager->remove($attendance);
        $entityManager->flush();
        $this->addFlash('success', 'You have left the training.');
        return $this->redirectToRoute('app_training_detail', ['id' => $training->getId()]);
    }

    #[Route('/my-trainings', name: 'app_my_trainings')]
    public function myTrainings(TrainingAttendanceRepository $trainingAttendanceRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $attendances = $trainingAttendanceRepository->findBy(['participant' => $user]);

        $trainingCards = [];
        foreach ($attendances as $attendance) {
            $training = $attendance->getTraining();
            $attendanceCount = $training->getAttendances()->count();
            $remainingSpots = $training->getCapacity() - $attendanceCount;

            $trainingCards[] = [
                'training' => $training,
                'attendanceCount' => $attendanceCount,
                'remainingSpots' => $remainingSpots,
            ];
        }

        return $this->render('training/my_trainings.html.twig', [
            'trainingCards' => $trainingCards,
        ]);
    }

}
