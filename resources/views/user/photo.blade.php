<form action="{{ route('user-photo-save') }}" method="post" enctype="multipart/form-data" id="photo-form">
    @csrf

    @php
        $user = auth()->user();
        $user_photo = $user->photo;
    @endphp
    @if($user_photo) {
    <center><img src="{{ $user_photo }}" class="img-responsive" alt="{{ $user->username }}" /></center>
    @endif

    <div class="row">
        <div class="col-md-12">
            {[ "type": "file", "label": "{{ tr('Foto utente') }}", "name": "photo", "help": "{{ tr('Dimensione consigliata 100x100 pixel') }}", "required": 1 ]}
        </div>
    </div>

    <button type="submit" class="btn btn-primary pull-right">
        <i class="fa fa-plus"></i> {{ tr('Aggiorna foto') }}
    </button>
    <div class="clearfix">&nbsp;</div>
</form>

<script>$(document).ready(init);</script>
