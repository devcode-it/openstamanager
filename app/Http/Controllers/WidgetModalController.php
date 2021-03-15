<?php

namespace App\Http\Controllers;

use App\OSM\Widgets\ModalWidget;
use App\OSM\Widgets\Widget;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WidgetModalController extends Controller
{
    public function modal(Request $request, $id)
    {
        $widget = Widget::find($id);
        $class = $widget->getManager();

        if (!($class instanceof ModalWidget)) {
            throw new NotFoundHttpException();
        }

        return $class->getModal();
    }
}
