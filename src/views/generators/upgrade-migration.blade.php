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

        Schema::table('{{ $laratrust['role_user_table'] }}', function (Blueprint $table) {
           // Drop user foreign key and primary with role_id
            $table->dropForeign(['{{ $laratrust['user_foreign_key'] }}']);
            $table->dropForeign(['{{ $laratrust['role_foreign_key'] }}']);
            $table->dropPrimary(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}']);

            $table->string('user_type');
        });

        DB::table('{{ $laratrust['role_user_table'] }}')->update(['user_type' => '{{ get_class($user) }}']);

        Schema::table('{{ $laratrust['role_user_table'] }}', function (Blueprint $table) {
            $table->foreign('{{ $laratrust['role_foreign_key'] }}')->references('id')->on('{{ $laratrust['roles_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->primary(['{{ $laratrust['user_foreign_key'] }}', '{{ $laratrust['role_foreign_key'] }}', 'user_type']);
        });



        Schema::table('{{ $laratrust['permission_user_table'] }}', function (Blueprint $table) {
           // Drop user foreign key and primary with permission_id
            $table->dropForeign(['{{ $laratrust['user_foreign_key'] }}']);
            $table->dropForeign(['{{ $laratrust['permission_foreign_key'] }}']);
            $table->dropPrimary(['{{ $laratrust['permission_foreign_key'] }}', '{{ $laratrust['user_foreign_key'] }}']);

            $table->string('user_type');
        });

        DB::table('{{ $laratrust['permission_user_table'] }}')->update(['user_type' => '{{ get_class($user) }}']);

        Schema::table('{{ $laratrust['permission_user_table'] }}', function (Blueprint $table) {
            $table->foreign('{{ $laratrust['permission_foreign_key'] }}')->references('id')->on('{{ $laratrust['permissions_table'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->primary(['{{ $laratrust['permission_foreign_key'] }}', '{{ $laratrust['user_foreign_key'] }}', 'user_type']);
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
