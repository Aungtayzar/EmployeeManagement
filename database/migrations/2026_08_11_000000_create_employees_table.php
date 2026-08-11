<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('job_role')->nullable();
            $table->date('join_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('
            INSERT INTO employees (user_id, first_name, last_name, phone, address, salary, job_role, join_date, created_at, updated_at, deleted_at)
            SELECT id, first_name, last_name, phone, address, salary, job_role, created_at, created_at, updated_at, deleted_at
            FROM users
        ');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'address', 'salary', 'job_role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->after('password');
            $table->text('address')->nullable()->after('phone');
            $table->decimal('salary', 12, 2)->default(0)->after('address');
            $table->string('job_role')->nullable()->after('system_role');
        });

        DB::statement('
            UPDATE users u
            INNER JOIN employees e ON u.id = e.user_id
            SET u.first_name = e.first_name,
                u.last_name = e.last_name,
                u.phone = e.phone,
                u.address = e.address,
                u.salary = e.salary,
                u.job_role = e.job_role
        ');

        Schema::dropIfExists('employees');
    }
};
