<?php
namespace App\People\Form\Subscriber;

use App\People\Form\CalendarGradeType;
use Hillrange\Form\Type\CollectionType;
use Hillrange\Form\Type\DateType;
use Hillrange\Form\Type\ImageType;
use App\Core\Type\SettingChoiceType;
use Hillrange\Form\Type\TextType;
use App\People\Form\UserType;
use App\People\Util\PersonManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PersonSubscriber implements EventSubscriberInterface
{
	/**
	 * @var PersonManager
	 */
	private $personManager;

	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;

	/**
	 * @var SessionInterface
	 */
	private $session;

	/**
	 * PersonSubscriber constructor.
	 *
	 * @param PersonManager          $pm
	 * @param EntityManagerInterface $om
	 * @param SessionInterface       $session
	 */
	public function __construct(PersonManager $pm, EntityManagerInterface $om, SessionInterface $session)
	{
		$this->personManager = $pm;
		$this->entityManager = $om;
		$this->session       = $session;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		// Tells the dispatcher that you want to listen on the form.pre_submit
		// event and that the preSubmit method should be called.
		return array(
			FormEvents::PRE_SET_DATA => 'preSetData',
			FormEvents::PRE_SUBMIT   => 'preSubmit',
		);
	}

	/**
	 * @param FormEvent $event
	 */
	public function preSetData(FormEvent $event)
	{
		$person = $event->getData();
		$form   = $event->getForm();

		if ($person->isStaff())
			$this->addStaffFields($form);

		if ($person->isStudent())
			$this->addStudentFields($form);

		if ($person->isUser())
		{
			$user = $person->getUser();
			$person->setUser($user);
			$form->add('user', UserType::class, ['data' => $user]);
			if (empty($person->getUser()->getEmail()) || $person->getUser()->getEmail() != $person->getEmail())
				$person->getUser()->setEmail($person->getEmail());
		}

		$event->setData($person);
	}

	/**
	 * @param FormEvent $event
	 */
	public function preSubmit(FormEvent $event)
	{
		$data   = $event->getData();
		$form   = $event->getForm();
		$person = $form->getData();
		$flush  = false;

		// Address Management
		unset($data['address1_list'], $data['address2_list']);
		if (!empty($data['address1']) || !empty($data['address2']))
		{
			if ($data['address1'] == $data['address2'])
				$data['address2'] = "";
			elseif (empty($data['address1']) && !empty($data['address2']))
			{
				$data['address1'] = $data['address2'];
				$data['address2'] = "";
			}
		}

		// Email Management
		if (!empty($data['email']) || !empty($data['email2']))
		{
			if ($data['email'] == $data['email2'])
				$data['email2'] = "";
			elseif (empty($data['email']) && !empty($data['email2']))
			{
				$data['email']  = $data['email2'];
				$data['email2'] = "";
			}
		}

		if ($person->isUser() && isset($data['user']['email']))
		{
			if (!in_array($data['user']['email'], [$data['email'], $data['email2']]))
			{
				$data['user']['email'] = $data['email'];
			}
		}

            //photo management
		if (empty($data['photo']))
		{
			$data['photo'] = $form->get('photo')->getNormData();
		}

		if ($flush)
			$this->entityManager->flush();

		if (empty($data['preferredName']))
			$data['preferredName'] = $data['firstName'];

		if ($data['photo'] instanceof File && empty($data['photo']->getFilename()))
			$data['photo'] = null;

		$event->setData($data);
	}

	/**
	 * Add Staff Fields
	 *
	 * @param $form
	 */
	private function addStaffFields($form)
	{
		$form
			->add('staffType', SettingChoiceType::class, array(
					'label'         => 'staff.stafftype.label',
					'setting_name'  => 'staff.categories',
					'attr'          => array(
						'class'         => 'staffMember',
					)
				)
			)
			->add('jobTitle', TextType::class, [
					'label'     => 'staff.jobTitle.label',
					'attr'      => [
						'class'     => 'staffMember',
					]
				]
			)
			->add('house', SettingChoiceType::class, [
					'label'              => 'staff.house.label',
					'placeholder'        => 'staff.house.placeholder',
					'required'           => false,
					'help'               => 'staff.house.help',
					'setting_name'       => 'house.list',
                    'translation_prefix' => true,
                    'setting_data_name'  => 'name',
				]
			)
		;

	}

	/**
	 * Add Staff Fields
	 *
	 * @param $form
	 */
	private function addStudentFields(Form $form)
	{
		$form
			->add('startAtSchool', DateType::class,
				[
					'years' => range(date('Y', strtotime('-25 years')), date('Y', strtotime('+1 year'))),
					'label' => 'student.startAtSchool.label',
					'help'  => 'student.startAtSchool.help',
					'attr'  => array(
						'class' => 'student',
					),
				]
			)
			->add('startAtThisSchool', DateType::class, array(
					'years' => range(date('Y', strtotime('-25 years')), date('Y', strtotime('+1 year'))),
					'label' => 'student.startAtThisSchool.label',
					'help'  => 'student.startAtThisSchool.help',
					'attr'  => array(
						'class' => 'student',
					),
				)
			)
			->add('lastAtThisSchool', DateType::class, array(
					'years'    => range(date('Y', strtotime('-5 years')), date('Y', strtotime('+18 months'))),
					'label'    => 'student.lastAtThisSchool.label',
					'help'  => 'student.lastAtThisSchool.help',
					'attr'     => array(
						'class' => 'student',
					),
					'required' => false,
				)
			)
			->add('firstLanguage', LanguageType::class, array(
					'label'       => 'student.language.first.label',
					'placeholder' => 'student.language.placeholder',
					'required'    => false,
				)
			)
			->add('secondLanguage', LanguageType::class, array(
					'label'       => 'student.language.second.label',
					'placeholder' => 'student.language.placeholder',
					'required'    => false,
				)
			)
			->add('thirdLanguage', LanguageType::class, array(
					'label'       => 'student.language.third.label',
					'placeholder' => 'student.language.placeholder',
					'required'    => false,
				)
			)
			->add('countryOfBirth', CountryType::class, array(
					'label'       => 'student.countryOfBirth.label',
					'placeholder' => 'student.countryOfBirth.placeholder',
					'required'    => false,
				)
			)
			->add('ethnicity', SettingChoiceType::class,
				array(
					'label'        => 'student.ethnicity.label',
					'placeholder'  => 'student.ethnicity.placeholder',
					'required'     => false,
					'setting_name' => 'ethnicity.list',
					'translation_prefix' => false,
				)
			)
			->add('religion', SettingChoiceType::class,
				array(
					'label'        => 'student.religion.label',
					'placeholder'  => 'student.religion.placeholder',
					'required'     => false,
					'setting_name' => 'religion.list',
					'translation_prefix'    => false,
                    'strict_validation' => false,
				)
			)
			->add('citizenship1', CountryType::class,
				array(
					'label'       => 'student.citizenship.1.label',
					'placeholder' => 'student.citizenship.placeholder',
					'required'    => false,
				)
			)
			->add('citizenship2', CountryType::class,
				array(
					'label'       => 'student.citizenship.2.label',
					'placeholder' => 'student.citizenship.placeholder',
					'required'    => false,
				)
			)
			->add('citizenship1Passport', TextType::class,
				array(
					'label'    => 'student.citizenship.passport.1.label',
					'required' => false,
				)
			)
			->add('citizenship2Passport', TextType::class,
				array(
					'label'    => 'student.citizenship.passport.2.label',
					'required' => false,
				)
			)
			->add('locker', TextType::class,
				array(
					'label'    => 'student.locker.label',
					'required' => false,
				)
			)
			->add('citizenship1PassportScan', ImageType::class, array(
					'help'       => 'student.passportScan.help',
					'attr'        => array(
						'imageClass' => 'headShot75',
					),
					'label'       => 'student.passportScan.label',
					'required'    => false,
					'deletePhoto' => $form->getConfig()->getOption('deletePassportScan'),
					'fileName'    => 'student_passport',
				)
			)
			->add('nationalIDCardNumber', TextType::class,
				[
					'label'    => 'student.nationalIDCardNumber.label',
					'required' => false,
                    'auto_complete' => 'cc-number',
				]
			)
			->add('nationalIDCardScan', ImageType::class, array(
					'help'       => 'student.nationalIDCardScan.help',
					'attr'        => array(
						'imageClass' => 'headShot75',
					),
					'label'       => 'student.nationalIDCardScan.label',
					'required'    => false,
					'deletePhoto' => $form->getConfig()->getOption('deleteIDScan'),
					'fileName'    => 'student_nationalid',
				)
			)
			->add('residencyStatus', SettingChoiceType::class,
				array(
					'label'        => 'student.residencyStatus.label',
					'placeholder'  => 'student.residencyStatus.placeholder',
					'required'     => false,
					'setting_name' => 'residency.list',
					'help' => 'student.residencyStatus.help',
				)
			)
			->add('visaExpiryDate', DateType::class, array(
					'years'    => range(date('Y', strtotime('-1 years')), date('Y', strtotime('+10 year'))),
					'label'    => 'student.visaExpiryDate.label',
					'help'  => 'student.visaExpiryDate.help',
					'attr'     => array(
						'class' => 'student',
					),
					'required' => false,
				)
			)
            ->add('house', SettingChoiceType::class, array(
                    'label'              => 'staff.house.label',
                    'placeholder'        => 'staff.house.placeholder',
                    'required'           => false,
                    'help'               => 'staff.house.help',
                    'setting_name'       => 'house.list',
                    'translation_prefix' => true,
                    'setting_data_name'  => 'name',
                )
            )
            ->add('calendarGrades', CollectionType::class,
                [
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => CalendarGradeType::class,
                    'route' => 'remove_student_calendar_grade',
                ]
            )
        ;
	}
}