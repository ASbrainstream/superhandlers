<?php

namespace App\Service;


use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Config\FosRest\BodyListener\ArrayNormalizerConfig;

class TaskService
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var $security
     */
    private $security;

    /**
     * @var $em
     */
    private $em;

    public function __construct(TaskRepository $taskRepository, Security $security, EntityManagerInterface $em)
    {
        $this->taskRepository = $taskRepository;
        $this->security = $security;
        $this->em = $em;
    }

    public function getTasks(): ?array
    {
        $taskList = $this->taskRepository->findAll();
        $data = [];
        if ($taskList) {
            foreach ($taskList as $task) {
                $tt['id'] = $task->getId();
                $tt['title'] = $task->getTitle();
                $tt['description'] = $task->getDescription();

                $data[] = $tt;
            }
        }
        return $data;
    }

    public function addTask(Request $request): Task
    {
        $user = $this->security->getUser();

        $task = new Task();
        $task->setTitle($request->get('title'));
        $task->setDescription($request->get('description'));
        $task->setUser($user);

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    public function updateTask(Request $request, int $id)
    {
        $request->request->add(json_decode($request->getContent(), true));

        $task = $this->taskRepository->find($id);
        if($task) {
            $task->setTitle($request->get('title'));
            $task->setDescription($request->get('description'));

            $this->em->persist($task);
            $this->em->flush();

            return true;
        }
        return false;
    }

    public function deleteTask(int $id)
    {
        $task = $this->taskRepository->find($id);

        if($task){
            $this->em->remove($task);
            $this->em->flush();

            return true;
        }
        return false;
    }
}