<?php echo '<?php' ?>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class LaratrustLevelsAdd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('{{ $laratrust['roles_table'] }}', function (Blueprint $table) {
            $table->integer('level')->default({{ $laratrust['level_default_value'] }});
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('{{ $laratrust['roles_table'] }}', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
}
