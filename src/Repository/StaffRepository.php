<?php
namespace App\Repository;

use App\Entity\Staff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * StaffRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StaffRepository extends ServiceEntityRepository
{
	/**
	 * StaffRepository constructor.
	 *
	 * @param RegistryInterface $registry
	 */
	public function __construct(RegistryInterface $registry)
	{
		parent::__construct($registry, Staff::class);
	}
	/**
	 * @param   integer $personID
	 *
	 * @return  Staff
	 */
	public function findOneByPerson($personID)
	{
		$staff = parent::find($personID);

		return $staff instanceof Staff ? $staff : new Staff();
	}

}
