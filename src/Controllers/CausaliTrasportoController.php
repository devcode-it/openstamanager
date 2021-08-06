<?php

namespace DevCode\CausaliTrasporto\Controllers;

use App\Http\Controllers\RequirementsController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use DevCode\Aggiornamenti\Aggiornamento;
use DevCode\Aggiornamenti\DowngradeException;

class CausaliTrasportoController extends Controller
{
    public $module;

    public function __construct()
    {
        $this->module = module('Causali');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('causali-trasporto::index', [
            'module' => $this->module,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function dettagli()
    {
        $args = [
            'module' => $this->module,
        ];
        return view('causali-trasporto::dettagli', $args);
    }
}
