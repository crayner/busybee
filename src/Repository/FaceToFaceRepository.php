<?php
namespace App\Repository;

use App\Entity\FaceToFace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ActivityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FaceToFaceRepository extends ServiceEntityRepository
{
	/**
	 * AddressRepository constructor.
	 *
	 * @param RegistryInterface $registry
	 */
	public function __construct(RegistryInterface $registry)
	{
		parent::__construct($registry, FaceToFace::class);
	}
}
