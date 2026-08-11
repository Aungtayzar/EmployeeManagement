<?php

namespace Tests\Feature;

use App\Jobs\ExportEmployeesJob;
use App\Models\Employee;
use App\Models\EmployeeTransferTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;

    protected string $employeeToken;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'system_role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $employeeUser = User::factory()->create([
            'email' => 'employee@test.com',
            'system_role' => 'employee',
            'password' => bcrypt('password'),
        ]);

        Employee::factory()->create([
            'user_id' => $employeeUser->id,
        ]);

        $this->adminToken = JWTAuth::fromUser($admin);
        $this->employeeToken = JWTAuth::fromUser($employeeUser);
    }

    private function withAuth(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_admin_can_create_employee(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createEmployee(input: {
                    first_name: "Alice"
                    last_name: "Wonder"
                    email: "alice@example.com"
                    salary: 60000
                    system_role: "employee"
                    job_role: "Developer"
                    password: "password123"
                    phone: "555-1234"
                    address: "123 Test St"
                }) {
                    id
                    first_name
                    last_name
                    email
                    salary
                    system_role
                    job_role
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJson([
            'data' => [
                'createEmployee' => [
                    'first_name' => 'Alice',
                    'last_name' => 'Wonder',
                    'email' => 'alice@example.com',
                    'salary' => 60000,
                    'system_role' => 'employee',
                    'job_role' => 'Developer',
                ],
            ],
        ]);
    }

    public function test_employee_cannot_create_employee(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createEmployee(input: {
                    first_name: "Alice"
                    last_name: "Wonder"
                    email: "alice@example.com"
                    salary: 60000
                    system_role: "employee"
                    job_role: "Developer"
                    password: "password123"
                }) {
                    id
                }
            }
        ', $this->withAuth($this->employeeToken));

        $response->assertJsonMissing(['data' => ['createEmployee' => ['id' => true]]]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createEmployee(input: {
                    first_name: "Alice"
                    last_name: "Wonder"
                    email: "alice@example.com"
                    salary: 60000
                    system_role: "employee"
                    password: "password123"
                }) {
                    id
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJsonMissing(['data' => ['createEmployee' => ['id' => true]]]);
    }

    public function test_admin_can_view_all_employees(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                employees(page: 1) {
                    data {
                        id
                        first_name
                    }
                    paginatorInfo {
                        total
                    }
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJsonStructure([
            'data' => [
                'employees' => [
                    'data',
                    'paginatorInfo' => ['total'],
                ],
            ],
        ]);
    }

    public function test_employee_cannot_view_all_employees(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                employees {
                    data {
                        id
                    }
                }
            }
        ', $this->withAuth($this->employeeToken));

        $response->assertJsonMissing(['data' => ['employees' => ['data' => true]]]);
    }

    public function test_admin_can_search_employees(): void
    {
        Employee::factory()->create(['first_name' => 'John', 'last_name' => 'UniqueName']);
        Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                employees(search: "UniqueName") {
                    data {
                        first_name
                        last_name
                    }
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJsonFragment(['first_name' => 'John']);
        $response->assertJsonMissing(['first_name' => 'Jane']);
    }

    public function test_admin_can_view_single_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query($id: ID!) {
                employee(id: $id) {
                    id
                    first_name
                    email
                }
            }
        ', $this->withAuth($this->adminToken), ['id' => $employee->id]);

        $response->assertJson([
            'data' => [
                'employee' => [
                    'id' => (string) $employee->id,
                    'email' => $employee->user->email,
                ],
            ],
        ]);
    }

    public function test_employee_cannot_view_other_employee(): void
    {
        $other = Employee::factory()->create();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query($id: ID!) {
                employee(id: $id) {
                    id
                }
            }
        ', $this->withAuth($this->employeeToken), ['id' => $other->id]);

        $response->assertJsonMissing(['data' => ['employee' => ['id' => true]]]);
    }

    public function test_employee_can_view_own_profile(): void
    {
        $user = User::where('email', 'employee@test.com')->first();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                me {
                    id
                    email
                    employee {
                        first_name
                        salary
                    }
                }
            }
        ', $this->withAuth($this->employeeToken));

        $response->assertJson([
            'data' => [
                'me' => [
                    'id' => (string) $user->id,
                    'email' => 'employee@test.com',
                ],
            ],
        ]);

        $this->assertNotNull($response->json('data.me.employee.salary'));
    }

    public function test_admin_can_update_employee(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'OldName']);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation($id: ID!) {
                updateEmployee(id: $id, input: {
                    first_name: "NewName"
                }) {
                    id
                    first_name
                }
            }
        ', $this->withAuth($this->adminToken), ['id' => $employee->id]);

        $response->assertJson([
            'data' => [
                'updateEmployee' => [
                    'id' => (string) $employee->id,
                    'first_name' => 'NewName',
                ],
            ],
        ]);
    }

    public function test_employee_cannot_update_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation($id: ID!) {
                updateEmployee(id: $id, input: {
                    first_name: "Hacked"
                }) {
                    id
                }
            }
        ', $this->withAuth($this->employeeToken), ['id' => $employee->id]);

        $response->assertJsonMissing(['data' => ['updateEmployee' => ['id' => true]]]);
    }

    public function test_admin_can_delete_and_restore_employee(): void
    {
        $employee = Employee::factory()->create();

        $deleteResponse = $this->graphQL(/** @lang GraphQL */ '
            mutation($id: ID!) {
                deleteEmployee(id: $id) {
                    message
                }
            }
        ', $this->withAuth($this->adminToken), ['id' => $employee->id]);

        $deleteResponse->assertJson([
            'data' => [
                'deleteEmployee' => [
                    'message' => 'Employee removed successfully.',
                ],
            ],
        ]);

        $this->assertNotNull(Employee::withTrashed()->find($employee->id)->deleted_at);
        $this->assertNotNull(User::withTrashed()->find($employee->user_id)->deleted_at);

        $restoreResponse = $this->graphQL(/** @lang GraphQL */ '
            mutation($id: ID!) {
                restoreEmployee(id: $id) {
                    message
                }
            }
        ', $this->withAuth($this->adminToken), ['id' => $employee->id]);

        $restoreResponse->assertJson([
            'data' => [
                'restoreEmployee' => [
                    'message' => 'Employee restored successfully.',
                ],
            ],
        ]);

        $this->assertNull(Employee::find($employee->id)->deleted_at);
        $this->assertNull(User::find($employee->user_id)->deleted_at);
    }

    public function test_admin_can_export_employees(): void
    {
        Queue::fake();

        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                exportEmployees {
                    id
                    status
                    url
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJsonPath('data.exportEmployees.status', 'pending');
        $response->assertJsonPath('data.exportEmployees.url', null);

        $taskId = $response->json('data.exportEmployees.id');

        $this->assertDatabaseHas('employee_transfer_tasks', [
            'id' => $taskId,
            'type' => 'export',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ExportEmployeesJob::class, fn (ExportEmployeesJob $job) => $job->taskId === $taskId);
    }

    public function test_admin_can_view_own_employee_transfer_task(): void
    {
        $admin = User::where('email', 'admin@test.com')->firstOrFail();
        $task = EmployeeTransferTask::create([
            'user_id' => $admin->id,
            'type' => 'import',
            'status' => 'completed',
            'success_count' => 2,
            'errors' => [['row' => 3, 'message' => 'The email field must be a valid email address.']],
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query($id: ID!) {
                employeeTransferTask(id: $id) {
                    id
                    type
                    status
                    success_count
                    errors {
                        row
                        message
                    }
                    error_message
                    url
                }
            }
        ', $this->withAuth($this->adminToken), ['id' => $task->id]);

        $response->assertJson([
            'data' => [
                'employeeTransferTask' => [
                    'id' => $task->id,
                    'type' => 'import',
                    'status' => 'completed',
                    'success_count' => 2,
                    'errors' => [
                        ['row' => 3, 'message' => 'The email field must be a valid email address.'],
                    ],
                    'url' => null,
                ],
            ],
        ]);
    }

    public function test_employee_cannot_export_employees(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                exportEmployees {
                    url
                }
            }
        ', $this->withAuth($this->employeeToken));

        $response->assertJsonMissing(['data' => ['exportEmployees' => ['url' => true]]]);
    }

    public function test_admin_can_trigger_employee_generation(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                generateEmployees(count: 10) {
                    job_id
                }
            }
        ', $this->withAuth($this->adminToken));

        $response->assertJsonStructure([
            'data' => [
                'generateEmployees' => [
                    'job_id',
                ],
            ],
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query {
                me {
                    id
                }
            }
        ');

        $response->assertJsonMissing(['data' => ['me' => ['id' => true]]]);
    }

    protected function graphQL(string $query, array $headers = [], array $variables = []): TestResponse
    {
        return $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables,
        ], $headers);
    }
}
