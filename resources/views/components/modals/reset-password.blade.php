{{-- Modal de restablecimiento de contraseña --}}
<div class="modal fade" id="resetPasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="resetPasswordModalLabel" aria-hidden="true" data-show="{{ session('status') ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto" id="resetPasswordModalLabel">
                    {{ __('Reset Password') }}
                </h5>
            </div>

            <div class="modal-body text-start">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form id="resetPasswordForm" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <x-forms.input label="{{ __('Email Address') }}" type="email" name="email" required autofocus autocomplete="email" col="col-md-12" />
                    </div>
                </form>
            </div>

            <div class="modal-footer justify-content-center">
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" icon="fa fa-paper-plane" text="{{ __('Send Password Reset Link')}}" form="resetPasswordForm"/>
                    </div>
                    <div class="col-md-6">
                        <x-buttons.button variant="secondary" icon="fa fa-times" text="Cancelar" data-bs-dismiss="modal"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
