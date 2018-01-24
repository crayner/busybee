<?php
namespace App\Repository;

use App\Entity\StudentCalendarGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Student Calendar Group Repository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StudentCalendarGroupRepository extends ServiceEntityRepository
{
	/**
	 * StudentCalendarGroupRepository constructor.
	 *
	 * @param RegistryInterface $registry
	 */
	public function __construct(RegistryInterface $registry)
	{
		parent::__construct($registry, StudentCalendarGroup::class);
	}
}