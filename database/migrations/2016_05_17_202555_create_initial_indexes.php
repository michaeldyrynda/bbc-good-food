<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ingredient_sections', function (Blueprint $table) {
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->unique([ 'recipe_id', 'sort_order', ]);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreign('ingredient_section_id')->references('id')->on('ingredient_sections')->onDelete('cascade');
        });

        Schema::table('metadata_recipe', function (Blueprint $table) {
            $table->foreign('metadata_id')->references('id')->on('metadatas')->onDelete('cascade');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
        });

        Schema::table('recipe_show', function (Blueprint $table) {
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('show_id')->references('id')->on('shows')->onDelete('cascade');
        });

        Schema::table('chef_recipe', function (Blueprint $table) {
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('cascade');
        });

        Schema::table('methods', function (Blueprint $table) {
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
