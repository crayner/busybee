<?php
namespace App\People\Entity;

use App\Entity\Person;
use App\Entity\Staff;
use App\Entity\Student;
use App\People\Util\PersonNameManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Hillrange\Security\Util\UserTrackInterface;
use Hillrange\Security\Util\UserTrackTrait;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Person Model
 *
 * @version    31st October 2016
 * @since      31st October 2016
 * @author     Craig Rayner
 */
abstract class PersonExtension implements UserTrackInterface
{
	use UserTrackTrait;

	/**
	 * get Titles
	 *
	 * @version    4th November 2016
	 * @since      4th November 2016
	 * @return    array
	 */
	public function getTitleList()
	{
		return $this->titleList;
	}

	/**
	 * set Title List
	 *
	 * @version    4th November 2016
	 * @since      4th November 2016
	 *
	 * @param    array $list
	 *
	 * @return    Person
	 */
	public function setTitleList($list)
	{
		$this->titleList = $list;

		return $this;
	}

	/**
	 * get Format Name
	 *
	 * @version    21st November 2016
	 * @since      21st November 2016
	 *
	 * @param   array $options
     * @deprecated Use PersonManager->fullName
     * @return    string
	 */
	public function formatName($options = array())
	{
	    /**
		 * Options
		 *
		 * surnameFirst boolean default = true
		 * preferredOnly boolean default = false
		 */
        @trigger_error('Use PersonManager->fullName', E_USER_DEPRECATED);
		if (empty($this->getSurname())) return '';

		$options['surnameFirst']  = !isset($options['surnameFirst']) ? true : $options['surnameFirst'];
		$options['preferredOnly'] = !isset($options['preferredOnly']) ? false : $options['preferredOnly'];

		if ($options['surnameFirst'])
		{
			if ($options['preferredOnly'])
				return $this->getSurname() . ': ' . $this->getPreferredName();

			return $this->getSurname() . ': ' . $this->getFirstName() . ' (' . $this->getPreferredName() . ')';
		}

		if ($options['preferredOnly'])
			return $this->getPreferredName() . ' ' . $this->getSurname();

		return $this->getFirstName() . ' (' . $this->getPreferredName() . ') ' . $this->getSurname();
	}

	/**
	 * @param string $float
	 *
	 * @return string
	 */
	public function getPhoto75($float = 'none')
	{
		$photo = $this->getIdentifier();
		if (empty($this->getPhoto()))
			$this->setPhoto($this->getBlankPhoto());
		if ($this->getPhoto() instanceof File && !empty($this->getPhoto()->getPathName()))
		{
			$div = getimagesize($this->getPhoto()->getPathName());
			$xx  = $div[0] / 75;

			$hh = intval($div[1] / $xx);

			$photo = '<img class="img-thumbnail img-photo75" title="' . $this->getIdentifier() . '" src="/' . $this->getPhoto()->getPathName() . '" width="75" style="float: ' . $float . '" />';

		}
		elseif (is_string($this->getPhoto()) && file_exists($this->getPhoto()))
		{
			$div = getimagesize($this->getPhoto());
			$xx  = $div[0] / 75;

			$hh = intval($div[1] / $xx);

			$photo = '<img class="img-thumbnail img-photo75" title="' . $this->getIdentifier() . '" src="/' . $this->getPhoto() . '" width="75" style="float: ' . $float . '" />';
		}

		return $photo;
	}

	/**
	 * @return File
	 */
	private function getBlankPhoto()
	{
		$photo = new File('img/DefaultPerson.png');

		return $photo;
	}

	/**
	 * @param string $float
	 *
	 * @return string
	 */
	public function getPhoto250($float = 'none')
	{
		$photo = $this->getIdentifier();

		if (empty($this->getPhoto())) $this->setPhoto($this->getBlankPhoto());

		if ($this->getPhoto() instanceof File && !empty($this->getPhoto()->getPathName()))
		{
			$div = getimagesize($this->getPhoto()->getPathName());
			$xx  = $div[0] / 250;

			$hh = intval($div[1] / $xx);

			$photo = '<img class="img-thumbnail img-photo250" title="' . $this->getIdentifier() . '" src="/' . $this->getPhoto()->getPathName() . '" width="250" height="' . $hh . '" style="width: 25opx; height: ' . $hh . 'px; float: ' . $float . '" />';
		}
		elseif (is_string($this->getPhoto()) && file_exists($this->getPhoto()))
		{
			$div = getimagesize($this->getPhoto());
			$xx  = $div[0] / 250;

			$hh = intval($div[1] / $xx);

			$photo = '<img class="img-thumbnail img-photo250" title="' . $this->getIdentifier() . '" src="/' . $this->getPhoto() . '" width="250" height="' . $hh . '" style="width: 250px; height: ' . $hh . 'px; float: ' . $float . '" />';
		}

		return $photo;
	}

	/**
	 * Get Person
	 *
	 * @return Person
	 */
	public function getPerson()
	{
		return $this;
	}

    /**
     * @param array $options
     * @return string
     */
	public function getFullName($options = array())
	{
	    return PersonNameManager::getFullName($this, $options);
	}

	/**
	 * @return bool
	 */
	public function isStaff(): bool
	{
		if ($this instanceof Staff)
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function isStudent(): bool
	{
		if ($this instanceof Student)
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function isUser(): bool
	{
		if ($this->getUser() === null)
			return false;

		return true;
	}

	/**
	 * @return bool
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * @return int|null
	 */
	public function getUserId(): ?int
	{
		return $this->getUser() ? $this->getUser()->getId() : null;
	}

    /**
     * isEqualTo
     *
     * @param Person $person
     * @return bool
     */
    public function isEqualTo(Person $person): bool
    {
        if ($this->getId() !== $person->getId())
            return false;

        if (get_class($this) !== get_class($person))
            return false;

        if ($this->getIdentifier() !== $person->getIdentifier())
            return false;

        if ($this->getSurname() !== $person->getSurname())
            return false;

        if ($this->getFirstName() !== $person->getFirstName())
            return false;

        return true;
    }

    static $personTypeList = [
        'person',
        'staff',
        'student',
        'contact',
    ];

    /**
     * switchTo
     *
     * @param string $type
     * @param Connection $connection
     * @return PersonExtension|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \TypeError
     */
    public function switchTo(string $type = 'person', ObjectManager $objectManager): ?PersonExtension
    {
        $tableName = $objectManager->getClassMetadata(Person::class)->getTableName();
        if (!in_array($type, self::$personTypeList))
            throw new \TypeError(sprintf('The person type of %s is not valid.', $type));

        $objectManager->getConnection()->exec('UPDATE `' . $tableName . '` SET `person_type` = "'.$type.'" WHERE `' . $tableName . '`.`id` = ' . $this->getId());
        $objectManager->persist($this);
        $objectManager->flush();
        $objectManager->refresh($this);

        return $this;
    }

}