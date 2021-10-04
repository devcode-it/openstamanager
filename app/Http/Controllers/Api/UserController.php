<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserController extends ApiController
{
    protected string|Model $model = User::class;
}
