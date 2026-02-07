<?php

namespace Modules\Impostazioni\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;

final class ListImpostazioniController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
         $user = Auth::user();
        if (!$user || !$user->is_admin) {
            throw new AuthorizationException();
        }
        
        $sezione = $request->query('sezione');
        $search = $request->query('ricerca');

        // Trova le impostazioni che corrispondono alla ricerca
        if (!empty($search)) {
            $impostazioni = Setting::where('nome', 'like', '%'.$search.'%')
                ->orWhere('sezione', 'like', '%'.$search.'%')
                ->get();
        } elseif (!empty($sezione)) {
            $impostazioni = Setting::where('sezione', $sezione)->get();
        } else {
            $impostazioni = Setting::all();
        }

        $results = [];
        foreach ($impostazioni as $impostazione) {
            $results[] = ImpostazioneResource::fromModel($impostazione);
        }

        return new JsonResponse($results);
    }
}
