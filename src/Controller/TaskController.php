<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;

class TaskController extends AbstractController
{
    /**
     * @var $taskService
     */
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * @Route("/api/tasklist", name="api_list")
     */
    public function index(): Response
    {
        $data = $this->taskService->getTasks();
        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/create-task", name="create_task", methods={"POST"})
     */
    public function createTask(Request $request)
    {
        $data = $this->taskService->addTask($request);
        return new JsonResponse("Task created successfully");
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @Route("api/update-task/{id}", name="update_task", methods={"PUT"})
     */
    public function updateTask(Request $request, int $id)
    {
        $data = $this->taskService->updateTask($request, $id);
        return new JsonResponse("Task updated successfully");
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @Route("/api/delete-task/{id}", name="delete_task", methods={"DELETE"})
     */
    public function deleteTask(int $id)
    {
        $this->taskService->deleteTask($id);
        return new JsonResponse("Task deleted successfully");
    }

}

