<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * @return Collection<int, Task>
     */
    public function getTasks(User $user, ?string $search = null): Collection
    {
        $query = $user->tasks()
            ->latest()
            ->orderByDesc('task_id');

        if (filled($search)) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * @throws ApiException
     */
    public function getTask(User $user, string $id): Task
    {
        $task = $user->tasks()->where('task_id', $id)->first();

        if (! $task) {
            throw new ApiException('Task not found', 404);
        }

        return $task;
    }

    /**
     * @param  array{title: string, description: string}  $data
     *
     * @throws ApiException
     */
    public function addTask(User $user, array $data): Task
    {
        $task = $user->tasks()->create($data);

        if (! $task) {
            throw new ApiException('Failed to create task', 500);
        }

        return $task;
    }

    /**
     * @param  array{title?: string, description?: string}  $data
     *
     * @throws ApiException
     */
    public function editTask(Task $task, array $data): Task
    {
        if (! $task->update($data)) {
            throw new ApiException('Failed to update task', 500);
        }

        return $task->refresh();
    }

    /**
     * @throws ApiException
     */
    public function deleteTask(Task $task): bool
    {
        if (! $task->delete()) {
            throw new ApiException('Failed to delete task', 500);
        }

        return true;
    }

    /**
     * @return Collection<int, Task>
     */
    public function searchTask(User $user, string $search): Collection
    {
        return $this->getTasks($user, $search);
    }
}
