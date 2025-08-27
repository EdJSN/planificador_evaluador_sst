{{-- Modal de inicio de sesión --}}
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto" id="loginModalLabel">Iniciar Sesión</h5>
            </div>
            <div class="modal-body text-start">
                <form id="loginForm" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <x-forms.input type="email" label="Correo electrónico" id="email" name="email"
                            col="col-md-12" required="true" autofocus />
                    </div>
                    <div class="mb-3">
                        <x-forms.input type="password" label="Contraseña" id="password" name="password" col="col-md-12"
                            required="true" />
                    </div>

                    {{-- Recuérdame y Olvidé contraseña --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                Recuérdame
                            </label>
                        </div>
                        <a class="text-decoration-none small text-body" href="#" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <x-buttons.button type="submit" id="loginBtn" text="Ingresar" form="loginForm" />
            </div>
        </div>
    </div>
</div>

