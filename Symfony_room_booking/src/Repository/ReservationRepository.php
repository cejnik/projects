<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findConflictingReservation(Room $room, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt, ?int $ignoreReservationId = null): ?Reservation
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.room = :room')
            ->andWhere('r.startsAt < :endsAt')
            ->andWhere('r.endsAt > :startsAt')
            ->andWhere('r.status = :status')
            ->setParameter('room', $room)
            ->setParameter('startsAt', $startsAt)
            ->setParameter('endsAt', $endsAt)
            ->setParameter('status', 'confirmed'); // cancelled rezervace se do conflict checku už nepočítají

        if ($ignoreReservationId) {
            $qb->andWhere('r.id != :ignoreReservationId');
            $qb->setParameter('ignoreReservationId', $ignoreReservationId);
        }
        return $qb->getQuery()->getOneOrNullResult();

    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
