<form action="{{ route('user-password-save') }}" method="post" id="password-form">
    @csrf

    <div class="row">
        <div class="col-md-12">
            {[ "type": "password", "label": "{{ tr('Password') }}", "name": "password", "required": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "password", "label": "{{ tr('Ripeti la password') }}", "name": "password_rep", "required": 1 ]}
        </div>
    </div>

    <button type="button" onclick="updatePassword()" class="btn btn-primary pull-right">
        <i class="fa fa-plus"></i> {{ tr('Aggiorna password') }}
    </button>
    <div class="clearfix">&nbsp;</div>
</form>

<script type="text/javascript">
    var min_length = {{ $min_length_password }};

    function updatePassword() {
        let password = $("#password").val();
        let password_rep = $("#password_rep").val();

        if(password === "" || password_rep === ""){
            swal({
                title: "{{ tr('Inserire una password valida') }}",
                type: "error",
            });
        }

        else if(password !== password_rep){
            swal({
                title: "{{ tr('Le password non coincidono') }}",
                type: "error",
            });
        }

        else if(password.length < min_length ){
            swal({
                title: "{{ tr('La password deve essere lunga minimo _MIN_ caratteri!', [
                    '_MIN_' => $min_length_password,
                ]) }}",
                type: "error",
            });
        }

        else {
            $("#password-form").submit();
        }
    }
</script>

<script>$(document).ready(init);</script>
