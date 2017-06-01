<?php echo '<?php' ?>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class LaratrustUpgradeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table for storing groups
        Schema::create('{{ $laratrust['groups_table'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('{{ $laratrust['role_user_table'] }}', function (Blueprint $table) {
            // Drop role foreign key and primary key
            $table->dropForeign(['{{ $laratrust['role_foreign_key'] }}']);
            $table->dropPrimary(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}', 'user_type']);

            // Add {{ $laratrust['group_foreign_key'] }} column
            $table->integer('{{ $laratrust['group_foreign_key'] }}')->unsigned()->nullable();

            // Create foreign keys
            $table->foreign('{{ $laratrust['role_foreign_key'] }}')->references('id')->on('{{ $laratrust['roles_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['group_foreign_key'] }}')->references('id')->on('{{ $laratrust['groups_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            // Create a unique key
            $table->unique(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}', 'user_type', '{{ $laratrust['group_foreign_key'] }}']);
        });

        Schema::table('{{ $laratrust['permission_user_table'] }}', function (Blueprint $table) {
           // Drop permission foreign key and primary key
            $table->dropForeign(['{{ $laratrust['permission_foreign_key'] }}']);
            $table->dropPrimary(['{{ $laratrust['permission_foreign_key'] }}', '{{ $laratrust['user_foreign_key'] }}', 'user_type']);

            // Add {{ $laratrust['group_foreign_key'] }} column
            $table->integer('{{ $laratrust['group_foreign_key'] }}')->unsigned()->nullable();

            $table->foreign('{{ $laratrust['permission_foreign_key'] }}')->references('id')->on('{{ $laratrust['permissions_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['group_foreign_key'] }}')->references('id')->on('{{ $laratrust['groups_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['permission_foreign_key'] }}', 'user_type', '{{ $laratrust['group_foreign_key'] }}']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
