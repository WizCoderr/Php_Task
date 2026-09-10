<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(public TaskService $taskService) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getTasks(
            $request->user(),
            $request->query('search')
        );

        return ApiResponse::success(
            TaskResource::collection($tasks),
            'Tasks retrieved successfully'
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->addTask(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            new TaskResource($task),
            'Task created successfully',
            201
        );
    }

    public function show(Request $request, string $task): JsonResponse
    {
        $task = $this->taskService->getTask($request->user(), $task);

        return ApiResponse::success(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    public function update(UpdateTaskRequest $request, string $task): JsonResponse
    {
        $task = $this->taskService->editTask(
            $this->taskService->getTask($request->user(), $task),
            $request->validated()
        );

        return ApiResponse::success(
            new TaskResource($task),
            'Task updated successfully'
        );
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        $this->taskService->deleteTask(
            $this->taskService->getTask($request->user(), $task)
        );

        return ApiResponse::success(
            null,
            'Task deleted successfully'
        );
    }
}
