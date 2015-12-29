<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumInteger('user_id')->unsigned()->nullable();
            $table->timestamp('index_timestamp');
            $table->char('md5', 32);
            $table->string('ip_addr', 64);
            $table->integer('file_size')->unsigned();
            $table->char('file_ext', 3);
            $table->smallInteger('width')->unsigned();
            $table->smallInteger('height')->unsigned();
            $table->char('rating', 1)->default('q');
            $table->smallInteger('preview_width')->unsigned();
            $table->smallInteger('preview_height')->unsigned();
            $table->enum('status', ['deleted', 'flagged', 'pending', 'active'])->default('active');
            $table->smallInteger('sample_width')->unsigned()->nullable();
            $table->smallInteger('sample_height')->unsigned()->nullable();
            $table->smallInteger('sample_size')->unsigned()->nullable();
            $table->text('tag_string');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('posts');
    }
}
