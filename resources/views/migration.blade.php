<?php echo '<?php' ?>


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
$table->id();
$table->string('name')->unique();
$table->string('display_name')->nullable();
$table->string('description')->nullable();
$table->timestamps();
});

// Create table for storing permissions
Schema::create('{{ $laratrust['tables']['permissions'] }}', function (Blueprint $table) {
$table->id();
$table->string('name')->unique();
$table->string('display_name')->nullable();
$table->string('description')->nullable();
$table->timestamps();
});

@if ($laratrust['teams']['enabled'])
        // Create table for storing teams
        Schema::create('{{ $laratrust['tables']['teams'] }}', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('display_name')->nullable();
        $table->string('description')->nullable();
        $table->timestamps();
        });

@endif
// Create table for associating roles to users and teams (Many To Many Polymorphic)
Schema::create('{{ $laratrust['tables']['role_user'] }}', function (Blueprint $table) {
$table->foreignId('{{ $laratrust['foreign_keys']['role'] }}')->constrained('{{ $laratrust['tables']['roles'] }}')->cascadeOnUpdate()->cascadeOnDelete();
$table->unsignedBigInteger('{{ $laratrust['foreign_keys']['user'] }}');
$table->string('user_type');
@if ($laratrust['teams']['enabled'])
    $table->foreignId('{{ $laratrust['foreign_keys']['team'] }}')->nullable()->constrained('{{ $laratrust['tables']['teams'] }}')->cascadeOnUpdate()->cascadeOnDelete();

    $table->unique(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['role'] }}', 'user_type', '{{ $laratrust['foreign_keys']['team'] }}']);
@else

    $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['role'] }}', 'user_type']);
@endif
});

// Create table for associating permissions to users (Many To Many Polymorphic)
Schema::create('{{ $laratrust['tables']['permission_user'] }}', function (Blueprint $table) {
$table->foreignId('{{ $laratrust['foreign_keys']['permission'] }}')->constrained('{{ $laratrust['tables']['permissions'] }}')->cascadeOnUpdate()->cascadeOnDelete();
$table->unsignedBigInteger('{{ $laratrust['foreign_keys']['user'] }}');
$table->string('user_type');
@if ($laratrust['teams']['enabled'])
    $table->foreignId('{{ $laratrust['foreign_keys']['team'] }}')->constrained('{{ $laratrust['tables']['teams'] }}')->cascadeOnUpdate()->cascadeOnDelete();

    $table->unique(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['permission'] }}', 'user_type', '{{ $laratrust['foreign_keys']['team'] }}']);
@else

    $table->primary(['{{ $laratrust['foreign_keys']['user'] }}', '{{ $laratrust['foreign_keys']['permission'] }}', 'user_type']);
@endif
});

// Create table for associating permissions to roles (Many-to-Many)
Schema::create('{{ $laratrust['tables']['permission_role'] }}', function (Blueprint $table) {
$table->foreignId('{{ $laratrust['foreign_keys']['permission'] }}')->constrained('{{ $laratrust['tables']['permissions'] }}')->cascadeOnUpdate()->cascadeOnDelete();
$table->foreignId('{{ $laratrust['foreign_keys']['role'] }}')->constrained('{{ $laratrust['tables']['roles'] }}')->cascadeOnUpdate()->cascadeOnDelete();

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
@if ($laratrust['teams']['enabled'])
        Schema::dropIfExists('{{ $laratrust['tables']['teams'] }}');
@endif
}
};
