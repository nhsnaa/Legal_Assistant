<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'user_role')) {
                $table->string('user_role')->default('staff')->after('password');
            }
        });

        if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'first_name')) {
            DB::table('users')
                ->whereNull('first_name')
                ->update([
                    'first_name' => DB::raw('name'),
                ]);
        }

        if (Schema::hasColumn('users', 'last_name')) {
            DB::table('users')
                ->whereNull('last_name')
                ->update([
                    'last_name' => '',
                ]);
        }

        if (Schema::hasColumn('users', 'user_role')) {
            DB::table('users')
                ->whereNull('user_role')
                ->update([
                    'user_role' => 'staff',
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }

            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('users', 'user_role')) {
                $table->dropColumn('user_role');
            }
        });
    }
};
