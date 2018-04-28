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
        Schema::create('{{ $laratrust['tables']['roles'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing permissions
        Schema::create('{{ $laratrust['tables']['permissions'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing permissions
        Schema::create('{{ $laratrust['tables']['permission_rule'] }}', function (Blueprint $table) {
            $table->string('permission_name')->primary();
            $table->binary('rule');
            $table->foreign('permission_name')->on('{{ $laratrust['tables']['permissions'] }}')->references('name')->onUpdate('cascade')->onDelete('cascade');
        });

@if ($laratrust['use_teams'])
        // Create table for storing teams
        Schema::create('{{ $laratrust['tables']['teams'] }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

@endif
        // Create table for associating roles to users and teams (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['tables']['role_user'] }}', function (Blueprint $table) {
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['role'] }}');
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['user'] }}');
            $table->string('user_type');
@if ($laratrust['use_teams'])
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['team'] }}')->nullable();
@endif

            $table->foreign('{{ $laratrust['foreign_keys']['role'] }}')->references('id')->on('{{ $laratrust['tables']['roles'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
@if ($laratrust['use_teams'])
            $table->foreign('{{ $laratrust['foreign_keys']['team'] }}')->references('id')->on('{{ $laratrust['tables']['teams'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['role'] }}', 'user_type', '{{ $laratrust['foreign_keys']['team'] }}']);
@else

            $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['role'] }}', 'user_type']);
@endif
        });

        // Create table for associating permissions to users (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['tables']['permission_user'] }}', function (Blueprint $table) {
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['permission'] }}');
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['user'] }}');
            $table->string('user_type');
@if ($laratrust['use_teams'])
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['team'] }}')->nullable();
@endif

            $table->foreign('{{ $laratrust['foreign_keys']['permission'] }}')->references('id')->on('{{ $laratrust['tables']['permissions'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
@if ($laratrust['use_teams'])
            $table->foreign('{{ $laratrust['foreign_keys']['team'] }}')->references('id')->on('{{ $laratrust['tables']['teams'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['permission'] }}', 'user_type', '{{ $laratrust['foreign_keys']['team'] }}']);
@else

            $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['permission'] }}', 'user_type']);
@endif
        });

        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create('{{ $laratrust['tables']['permission_role'] }}', function (Blueprint $table) {
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['permission'] }}');
            $table->unsignedInteger('{{ $laratrust['foreign_keys']['role'] }}');

            $table->foreign('{{ $laratrust['foreign_keys']['permission'] }}')->references('id')->on('{{ $laratrust['tables']['permissions'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['foreign_keys']['role'] }}')->references('id')->on('{{ $laratrust['tables']['roles'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['permission'] }}', '{{ $laratrust['foreign_keys']['role'] }}']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{{ $laratrust['tables']['permission_user'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['permission_role'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['permissions'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['role_user'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['roles'] }}');
@if ($laratrust['use_teams'])
        Schema::dropIfExists('{{ $laratrust['tables']['teams'] }}');
@endif
    }
}
