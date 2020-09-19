<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LaratrustSetupTables extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->createMainTables();
        $this->createRoleUserTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laratrust.tables.permission_owner'));
        Schema::dropIfExists(config('laratrust.tables.permissions'));
        Schema::dropIfExists(config('laratrust.tables.role_user'));
        Schema::dropIfExists(config('laratrust.tables.roles'));

        if ($this->IsTeamsEnabled()) {
            Schema::dropIfExists(config('laratrust.tables.teams'));
        }
    }

    private function createMainTables(): void
    {
        // Create table for storing roles
        Schema::create(config('laratrust.tables.roles'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing permissions
        Schema::create(config('laratrust.tables.permissions'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        if ($this->IsTeamsEnabled()) // Create table for storing teams
        {
            Schema::create(config('laratrust.tables.teams'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('display_name')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createRoleUserTable(): void
    {
        // Create table for associating roles to users and teams (Many To Many Polymorphic)
        Schema::create(config('laratrust.tables.role_user'), function (Blueprint $table) {
            $table->unsignedBigInteger(config('laratrust.foreign_keys.role'));
            $table->unsignedBigInteger(config('laratrust.foreign_keys.user'));
            $table->string('user_type');
            if ($this->IsTeamsEnabled()) {
                $table->unsignedBigInteger(config('laratrust.foreign_keys.team'))->nullable();
            }

            $table->foreign(config('laratrust.foreign_keys.role'))->references('id')->on(config('laratrust.tables.roles'))
                ->onUpdate('cascade')->onDelete('cascade');


            if ($this->IsTeamsEnabled()) {

                $table->foreign(config('laratrust.foreign_keys.team'))->references('id')->on(config('laratrust.tables.teams'))
                    ->onUpdate('cascade')->onDelete('cascade');
                $table->unique([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.role'), 'user_type', config('laratrust.foreign_keys.team')]);

            } else {

                $table->primary([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.role'), 'user_type']);

            }
        });
    }


    /**
     * @return bool
     */
    private function IsTeamsEnabled()
    {
        return config('laratrust.teams.enabled', false);
    }
}
