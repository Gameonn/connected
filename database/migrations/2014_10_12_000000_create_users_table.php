<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->bigInteger('fbid');
			$table->string('linkedin_id');
			$table->string('apn_id');
			$table->string('reg_id');
			$table->string('full_name',100);
			$table->string('email')->unique()->nullable();
			$table->string('password', 60);
			$table->string('phone_number',13);
			$table->string('profile_pic');
			$table->double('lat');
            $table->double('lng');
            $table->integer('age');
            $table->string('fraternity');
            $table->string('bio');
            $table->string('profile_video');
            $table->enum('user_type', ['intern', 'employee','employer']);
            $table->string('access_token');
			$table->rememberToken();
			$table->timestamps(); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
