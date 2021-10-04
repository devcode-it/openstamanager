<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use F9Web\ApiResponseHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use ApiResponseHelpers;

    protected string|Model $model = Model::class;
    protected string|ApiResource $resource = ApiResource::class;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->respondWithSuccess(new $this->resource($this->model::all()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $instance = new $this->model();
        $instance->fill($request->all());

        $created = $instance->save();

        return $created ? $this->respondCreated(new $this->resource($instance)) : $this->respondError();
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $instance = $this->model::find($id);

        if (!assert($instance instanceof Model)) {
            return $this->respondNotFound(__('Risorsa non trovata.'));
        }

        return $this->respondWithSuccess(new $this->resource($this->model::find($id)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $instance = $this->model::find($id);
        if (!assert($instance instanceof Model)) {
            return $this->respondNotFound(__('Risorsa non trovata.'));
        }

        $instance->fill($request->all());
        $updated = $instance->save();

        return $updated ? $this->respondWithSuccess($instance) : $this->respondError();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $instance = $this->model::find($id);

        if (!assert($instance instanceof Model)) {
            return $this->respondNotFound(__('Risorsa non trovata.'));
        }

        $deleted = $instance->delete();

        return $deleted ? $this->respondNoContent() : $this->respondError();
    }
}
