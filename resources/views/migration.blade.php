<?php echo '<?php' ?>


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
        // Create table for storing groups
        Schema::create('{{ $laratrust['tables']['groups'] }}', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing roles
        Schema::create('{{ $laratrust['tables']['roles'] }}', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing permissions
        Schema::create('{{ $laratrust['tables']['permissions'] }}', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for associating roles to groups (Many To Many)
        Schema::create('{{ $laratrust['tables']['group_role'] }}', function (Blueprint $table) {

            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['role'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['group'] }}');

            $table->foreign('{{ $laratrust['foreign_keys']['role'] }}')->references('id')->on('{{ $laratrust['tables']['roles'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['foreign_keys']['group'] }}')->references('id')->on('{{ $laratrust['tables']['groups'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['role'] }}', '{{ $laratrust['foreign_keys']['group'] }}']);
        });

        // Create table for associating groups to users (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['tables']['group_user'] }}', function (Blueprint $table) {
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['group'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['user'] }}');
            $table->string('user_type');

            $table->foreign('{{ $laratrust['foreign_keys']['group'] }}')->references('id')->on('{{ $laratrust['tables']['groups'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['group'] }}', '{{ $laratrust['foreign_keys']['user'] }}', 'user_type']);
        });

        // Create table for associating roles to users (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['tables']['role_user'] }}', function (Blueprint $table) {
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['role'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['user'] }}');
            $table->string('user_type');

            $table->foreign('{{ $laratrust['foreign_keys']['role'] }}')->references('id')->on('{{ $laratrust['tables']['roles'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['role'] }}', 'user_type']);
        });

        // Create table for associating permissions to users (Many To Many Polymorphic)
        Schema::create('{{ $laratrust['tables']['permission_user'] }}', function (Blueprint $table) {
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['permission'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['user'] }}');
            $table->string('user_type');

            $table->foreign('{{ $laratrust['foreign_keys']['permission'] }}')->references('id')->on('{{ $laratrust['tables']['permissions'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['permission'] }}', 'user_type']);
        });

        // Create table for associating permissions to groups (Many-to-Many)
        Schema::create('{{ $laratrust['tables']['permission_group'] }}', function (Blueprint $table) {
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['permission'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['group'] }}');

            $table->foreign('{{ $laratrust['foreign_keys']['permission'] }}')->references('id')->on('{{ $laratrust['tables']['permissions'] }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('{{ $laratrust['foreign_keys']['group'] }}')->references('id')->on('{{ $laratrust['tables']['groups'] }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['{{ $laratrust['foreign_keys']['permission'] }}', '{{ $laratrust['foreign_keys']['group'] }}']);
        });

        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create('{{ $laratrust['tables']['permission_role'] }}', function (Blueprint $table) {
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['permission'] }}');
            $table->unsignedBigInteger('{{ $laratrust['foreign_keys']['role'] }}');

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
        Schema::dropIfExists('{{ $laratrust['tables']['permission_group'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['permissions'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['role_user'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['group_role'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['roles'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['group_user'] }}');
        Schema::dropIfExists('{{ $laratrust['tables']['groups'] }}');
    }
}
