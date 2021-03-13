@foreach($requirements as $group => $elements)
    @php
    $general_status = true;
    foreach ($elements as $element){
        $general_status &= $element['status'];
    }
    @endphp

    <div class="box box-outline box-{{ $general_status ? 'success collapsed-box' : 'danger' }}">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $group }}</h3>

            @if($general_status)
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            @endif
        </div>
        <div class="box-body no-padding">
            <table class="table">

                @foreach($elements as $name => $element)
                    <tr class="{{ $element['status'] ? 'success' : 'danger' }}">
                        <td style="width: 10px"><i class="fa fa-{{ $element['status'] ? 'check' : 'times' }}"></i></td>
                        <td>{{ $name }}</td>
                        <td>{!! $element['description'] !!}</td>
                    </tr>
                @endforeach

            </table>
        </div>
    </div>
@endforeach
