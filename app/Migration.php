<?php

declare(strict_types=1);

namespace App;

use Leaf\Auth;

interface Migration
{
    public function up(Auth $auth): void;
}
