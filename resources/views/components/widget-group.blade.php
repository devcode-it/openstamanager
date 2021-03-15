@if(!empty($widgets))
    @php
        $row_max = count($widgets);
        if ($row_max > 4) {
            $row_max = 4;
        } elseif ($row_max < 2) {
            $row_max = 2;
        }
    @endphp
<ul class="row widget" id="widget-{{ $position }}">
    @foreach($widgets as $widget)
    <li class="col-sm-6 col-md-4 col-lg-{{ intval(12 / $row_max) }} li-widget" id="widget_{{ $widget['id'] }}">
        <x-widget :id="$widget['id']"></x-widget>
    </li>
    @endforeach
</ul>
@endif
