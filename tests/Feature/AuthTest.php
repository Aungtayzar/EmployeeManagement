<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(email: "admin@example.com", password: "password") {
                    token
                }
            }
        ');

        $response->assertJson([
            'data' => [
                'login' => [
                    'token' => $response->json('data.login.token'),
                ],
            ],
        ]);

        $this->assertNotNull($response->json('data.login.token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(email: "admin@example.com", password: "wrongpassword") {
                    token
                }
            }
        ');

        $response->assertJsonMissing(['data' => ['login' => ['token' => true]]]);
    }

    public function test_refresh_token(): void
    {
        $token = auth('api')->attempt([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                refreshToken {
                    token
                }
            }
        ', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertJsonStructure(['data' => ['refreshToken' => ['token']]]);
        $this->assertNotNull($response->json('data.refreshToken.token'));
    }

    public function test_refresh_token_without_token_fails(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                refreshToken {
                    token
                }
            }
        ');

        $response->assertJsonMissing(['data' => ['refreshToken' => ['token' => true]]]);
    }

    public function test_logout(): void
    {
        $token = auth('api')->attempt([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                logout {
                    message
                }
            }
        ', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertJson([
            'data' => [
                'logout' => [
                    'message' => 'Successfully logged out.',
                ],
            ],
        ]);

        $invalidResponse = $this->graphQL(/** @lang GraphQL */ '
            query {
                me {
                    id
                }
            }
        ', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $invalidResponse->assertJsonMissing(['data' => ['me' => ['id' => true]]]);
    }

    public function test_logout_without_token_fails(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                logout {
                    message
                }
            }
        ');

        $response->assertJsonMissing(['data' => ['logout' => ['message' => 'Successfully logged out.']]]);
    }

    protected function graphQL(string $query, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/graphql', ['query' => $query], $headers);
    }
}
