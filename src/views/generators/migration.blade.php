<?php echo '<?php' ?>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class LaratrustSetupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table for storing roles
        Schema::create('{{ $laratrust['roles_table'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing teams
        Schema::create('{{ $laratrust['teams_table'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for associating roles to users and teams (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['role_user_table'] }}', function (Blueprint $table) {
            $table->integer('{{ $laratrust['user_foreign_key'] }}')->unsigned();
            $table->integer('{{ $laratrust['role_foreign_key'] }}')->unsigned();
            $table->integer('{{ $laratrust['team_foreign_key'] }}')->unsigned()->nullable();
            $table->string('user_type');

            $table->foreign('{{ $laratrust['role_foreign_key'] }}')->references('id')->on('{{ $laratrust['roles_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['team_foreign_key'] }}')->references('id')->on('{{ $laratrust['teams_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}', 'user_type', '{{ $laratrust['team_foreign_key'] }}']);
        });

        // Create table for storing permissions
        Schema::create('{{ $laratrust['permissions_table'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create('{{ $laratrust['permission_role_table'] }}', function (Blueprint $table) {
            $table->integer('{{ $laratrust['permission_foreign_key'] }}')->unsigned();
            $table->integer('{{ $laratrust['role_foreign_key'] }}')->unsigned();

            $table->foreign('{{ $laratrust['permission_foreign_key'] }}')->references('id')->on('{{ $laratrust['permissions_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['role_foreign_key'] }}')->references('id')->on('{{ $laratrust['roles_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['permission_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}']);
        });

        // Create table for associating permissions to users (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['permission_user_table'] }}', function (Blueprint $table) {
            $table->integer('{{ $laratrust['permission_foreign_key'] }}')->unsigned();
            $table->integer('{{ $laratrust['user_foreign_key'] }}')->unsigned();
            $table->string('user_type');
            $table->integer('{{ $laratrust['team_foreign_key'] }}')->unsigned()->nullable();

            $table->foreign('{{ $laratrust['permission_foreign_key'] }}')->references('id')->on('{{ $laratrust['permissions_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['team_foreign_key'] }}')->references('id')->on('{{ $laratrust['teams_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['permission_foreign_key'] }}', 'user_type', '{{ $laratrust['team_foreign_key'] }}']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{{ $laratrust['permission_user_table'] }}');
        Schema::dropIfExists('{{ $laratrust['permission_role_table'] }}');
        Schema::dropIfExists('{{ $laratrust['permissions_table'] }}');
        Schema::dropIfExists('{{ $laratrust['role_user_table'] }}');
        Schema::dropIfExists('{{ $laratrust['teams_table'] }}');
        Schema::dropIfExists('{{ $laratrust['roles_table'] }}');
    }
}
