<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends ServiceEntityRepository
{
	public function __construct(RegistryInterface $registry)
	{
		parent::__construct($registry, User::class);
	}

	/**
	 * find
	 *
	 * @param mixed $id
	 *
	 * @return User
	 */
	public function find($id, $lockMode = NULL, $lockVersion = NULL): User
	{
		$entity = parent::find($id, $lockMode, $lockVersion);

		if (is_null($entity) && intval($id) === 1)
			$entity = new User();

		return $entity;
	}
}
