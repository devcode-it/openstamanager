<?php

namespace App\Restify;

use App\Models\User;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Facades\Hash;

class UserRepository extends Repository
{
    public static string $model = User::class;

    public static array $sort = ['id', 'username', 'email', 'created_at', 'updated_at'];

    public static array $match = [
        'id' => 'int',
        'username' => 'string',
    ];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('username')->rules('required'),
            field('email')->storingRules(['required', 'unique:users'])->updatingRules('unique:users'),
            field('created_at')->label('createdAt')->readonly(),
            field('updated_at')->label('updatedAt')->readonly(),
        ];
    }

    public function fieldsForStore(RestifyRequest $request): array
    {
        return [
            ...$this->fields($request),
            field('password')
                ->rules('required')
                ->storeCallback(static fn (string $value) => Hash::make($value)),
        ];
    }
}
