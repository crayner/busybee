<?php
namespace App\School\Util;

use App\Core\Manager\MessageManager;
use App\Core\Manager\TabManager;
use App\Core\Manager\TabManagerInterface;
use App\Entity\Course;
use App\Entity\Department;
use App\Entity\DepartmentMember;
use App\Entity\Staff;
use Doctrine\ORM\EntityManagerInterface;
use Hillrange\Form\Util\CollectionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

class DepartmentManager extends TabManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var null|Department
     */
    private $department;

    /**
     * @var null|Course
     */
    private $course;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var string
     */
    private $status = 'default';

    /**
     * @var null|Staff
     */
    private $member;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $stack;

    /**
     * DepartmentManager constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, MessageManager $messageManager, RouterInterface $router, RequestStack $stack)
    {
        $this->entityManager = $entityManager;
        $this->messageManager = $messageManager;
        $this->messageManager->setDomain('School');
        $this->router = $router;
        $this->stack = $stack;
    }

    /**
     * @param $id
     * @return Department
     */
    public function findDepartment($id): Department
    {
        $this->department = $this->entityManager->getRepository(Department::class)->find(intval($id));
        return $this->getDepartment();
    }

    /**
     * @return Department
     */
    public function getDepartment(): Department
    {
        if (empty($this->department))
            $this->department = new Department();

        return $this->department;
    }

    /**
     * @param $cid
     */
    public function removeCourse($cid)
    {
        $this->getDepartment();

        $this->findCourse($cid);
        $this->setStatus('warning');

        if ($cid === 'ignore')
            return ;

        if (empty($this->course)) {
            $this->messageManager->add('warning', 'department.course.missing.warning', ['%{course}' => $cid]);
            return;
        }

        if ($this->department->getCourses()->contains($this->course)) {
            // Course is NOT Deleted, only removed from Department.
            $this->department->removeCourse($this->course);
            $this->entityManager->persist($this->course);
            $this->entityManager->persist($this->department);
            $this->entityManager->flush();
            $this->entityManager->refresh($this->department);
            $this->setStatus('success');

            $this->messageManager->add('success', 'department.course.removed.success', ['%{course}' => $this->course->getFullName()]);
        } else {
            $this->setStatus('info');
            $this->messageManager->add('info', 'department.course.removed.info', ['%{course}' => $this->course->getFullName()]);
        }
    }

    /**
     * @param $id
     * @return Department
     */
    public function findCourse($id): ?Course
    {
        $this->course = $this->entityManager->getRepository(Course::class)->find(intval($id));

        return $this->getCourse();
    }

    /**
     * @return null|Course
     */
    public function getCourse(): ?Course
    {
        return $this->course;
    }

    /**
     * @return MessageManager
     */
    public function getMessageManager(): MessageManager
    {
        return $this->messageManager;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return empty($this->status) ? 'default' : $this->status;
    }

    /**
     * @param string $status
     * @return DepartmentManager
     */
    public function setStatus(string $status): DepartmentManager
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $cid
     */
    public function removeMember($id, $cid)
    {
        $this->findDepartment($id);
        if ($cid === 'ignore')
            return ;

        $this->getDepartment();

        $this->findMember($cid);
        $this->setStatus('warning');

        if (empty($this->member)) {
            $this->messageManager->add('warning', 'department.member.missing.warning', ['%{member}' => $cid]);
            return;
        }

        if ($this->department->getMembers()->contains($this->member)) {
            // Staff is NOT Deleted, but the DepartmentMember link is deleted.
            $this->department->removeMember($this->member);
            $this->entityManager->remove($this->member);
            $this->entityManager->persist($this->department);
            $this->entityManager->flush();
            $this->setStatus('success');

            $this->messageManager->add('success', 'department.member.removed.success', ['%{member}' => $this->member->getFullStaffName()]);
        } else {
            $this->setStatus('info');
            $this->messageManager->add('info', 'department.member.removed.info', ['%{member}' => $this->member->getFullStaffName()]);
        }
    }

    /**
     * @param $id
     * @return Department
     */
    public function findMember($id): ?DepartmentMember
    {
        $this->member = $this->entityManager->getRepository(DepartmentMember::class)->find(intval($id));

        return $this->getMember();
    }

    /**
     * @return Staff|null
     */
    public function getMember(): ?DepartmentMember
    {
        return $this->member;
    }

    /**
     * @return Department|null
     */
    public function refreshDepartment(): ?Department
    {
        if (empty($this->department))
            return $this->department;

        try {
            $this->entityManager->refresh($this->department);
            return $this->department->refresh();
        } catch (\Exception $e) {
            return $this->department;
        }
    }

    /**
     * @return array
     */
    public function getTabs(): array
    {
        return Yaml::parse("
department_details:
    label: department.details.tab
    include: Department/department_details.html.twig
    message: departmentDetailsMessage
    translation: School
department_tutor_collection:
    label: department.staff.tab
    include: Department/department_staff.html.twig
    message: departmentStaffMessage
    translation: School
    display: hasMembers
department_course_collection:
    label: department.courses.tab
    include: Department/department_courses.html.twig
    message: departmentCoursesMessage
    translation: School
    display: requireCourses
");
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function requireCourses(): bool
    {
        if ($this->getDepartment()->getType() !== 'Administration')
            return true;
        return false;
    }

    public function hasMembers(): bool
    {
        if (is_null($this->getDepartment()->getType()))
            return false;
        return true;
    }
}