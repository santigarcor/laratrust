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

        // Create table for associating roles to users (Many-to-Many)
        Schema::create('{{ $laratrust['role_user_table'] }}', function (Blueprint $table) {
            $table->integer('{{ $laratrust['user_foreign_key'] }}')->unsigned();
            $table->integer('{{ $laratrust['role_foreign_key'] }}')->unsigned();

            $table->foreign('{{ $laratrust['user_foreign_key'] }}')->references('{{ $user->getKeyName() }}')->on('{{ $user->getTable() }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['role_foreign_key'] }}')->references('id')->on('{{ $laratrust['roles_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}']);
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('{{ $laratrust['permission_role_table'] }}');
        Schema::drop('{{ $laratrust['permissions_table'] }}');
        Schema::drop('{{ $laratrust['role_user_table'] }}');
        Schema::drop('{{ $laratrust['roles_table'] }}');
    }
}
