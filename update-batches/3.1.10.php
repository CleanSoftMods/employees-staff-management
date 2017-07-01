<?php

use Illuminate\Support\Facades\Schema;

Schema::table('password_resets', function ($table) {
    $table->dropColumn('id');
    $table->dropColumn('expired_at');
});
