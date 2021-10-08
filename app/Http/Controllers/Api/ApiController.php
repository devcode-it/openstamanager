<?php /** @noinspection PhpUndefinedMethodInspection */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maicol07\LaravelJsonApiResource\Http\Resource\JsonApi\Resource;
use Maicol07\LaravelJsonApiResource\Http\Resource\JsonApi\ResourceCollection;

class ApiController extends Controller
{
    protected string|Model $model = Model::class;

    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        return new ResourceCollection($this->model::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse|Resource
    {
        $instance = new $this->model();
        $instance->fill($request->input('data.attributes'));

        $created = $instance->save();

        return $created ? new Resource($instance) : $this->error(Response::HTTP_INTERNAL_SERVER_ERROR, __('Impossibile creare la risorsa'));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): Resource|JsonResponse
    {
        $instance = $this->model::find($id);

        if (!assert($instance instanceof Model)) {
            return $this->error(Response::HTTP_NOT_FOUND, __('Risorsa non trovata.'));
        }

        return new Resource($instance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): Resource|JsonResponse
    {
        $instance = $this->model::find($id);
        if (!assert($instance instanceof Model)) {
            return $this->error(Response::HTTP_NOT_FOUND, __('Risorsa non trovata.'));
        }

        $instance->fill($request->input('data.attributes'));
        $updated = $instance->save();

        return $updated ? new Resource($instance) : $this->error(Response::HTTP_INTERNAL_SERVER_ERROR, __('Impossibile salvare le modifiche'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Response|JsonResponse
    {
        $instance = $this->model::find($id);

        if (!assert($instance instanceof Model)) {
            return $this->error(Response::HTTP_NOT_FOUND, __('Risorsa non trovata.'));
        }

        $deleted = $instance->delete();

        return $deleted ? response()->noContent() : $this->error(Response::HTTP_INTERNAL_SERVER_ERROR, __('Impossibile eliminare la risorsa'));
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function error(int $status, string $title, ?string $detail = null): JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'status' => $status,
                    'title' => $title,
                    'detail' => $detail,
                ],
            ],
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
