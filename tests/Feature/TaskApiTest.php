<?php

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to list tasks', function () {
    $this->getJson('/api/tasks')->assertUnauthorized();
});

it('lists only the authenticated users tasks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Task::factory()->count(2)->for($user)->create();
    Task::factory()->for($other)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Tasks retrieved successfully')
        ->assertJsonCount(2, 'data');
});

it('creates a task for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = [
        'title' => 'Ship feature',
        'description' => 'Finish the task API',
    ];

    $this->postJson('/api/tasks', $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Task created successfully')
        ->assertJsonPath('data.title', 'Ship feature');

    $this->assertDatabaseHas('tasks', [
        ...$payload,
        'user_id' => $user->id,
    ]);
});

it('shows a task owned by the authenticated user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'title' => 'Review PR',
        'description' => 'Check the service layer',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->task_id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.task_id', $task->task_id)
        ->assertJsonPath('data.title', 'Review PR');
});

it('returns not found for a missing task', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/tasks/00000000-0000-0000-0000-000000000000')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Task not found');
});

it('returns not found for another users task', function () {
    $user = User::factory()->create();
    $otherTask = Task::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$otherTask->task_id}")
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Task not found');
});

it('updates a task owned by the authenticated user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->task_id}", [
        'title' => 'Updated title',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Updated title');

    $this->assertDatabaseHas('tasks', [
        'task_id' => $task->task_id,
        'title' => 'Updated title',
        'user_id' => $user->id,
    ]);
});

it('deletes a task owned by the authenticated user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->task_id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Task deleted successfully');

    $this->assertDatabaseMissing('tasks', [
        'task_id' => $task->task_id,
    ]);
});

it('validates required fields when creating a task', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tasks', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description']);
});

it('searches only the authenticated users tasks by title', function () {
    $user = User::factory()->create();

    Task::factory()->for($user)->create(['title' => 'Alpha task', 'description' => 'one']);
    Task::factory()->for($user)->create(['title' => 'Beta item', 'description' => 'two']);
    Task::factory()->create(['title' => 'Alpha other', 'description' => 'three']);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?search=Alpha')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Alpha task');
});
