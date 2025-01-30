<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up()
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'type' => 'admin',
                'password' => Hash::make('admin98'),
                'sex' => 'masculin'
            ]
        );
    }

    public function down()
    {
        User::where('email', 'admin@gmail.com')->delete();
    }
};

