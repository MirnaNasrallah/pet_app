
<div class="container mt-4">
    <div class="row text-center">
        <div class="col-md-10 m-auto">
            <div class="card card-block d-flex">



                <div class="row">
                    <div class="card-body col-7 align-items-center d-flex justify-content-center">
                        <form style="width: 550px;" method="GET" action="{{ route('login') }}">
                            @csrf

                            <div class="row mb-4">
                                <label for="email" class="col-lg-4 col-form-label text-md-end font-weight-bold text-center">{{ __('Email Address') }}</label>

                                <div class="col-lg-8 input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text" id="forrEmail">
                                            <span class="material-icons">
                                                email
                                            </span>
                                        </div>
                                    </div>
                                    <input aria-describedby="forrEmail" id="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter Your Email Address" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password" class="col-lg-4 col-form-label text-md-end font-weight-bold text-center">{{ __('Password') }}</label>

                                <div class="col-lg-8 input-group">
                                    <input aria-describedby="btnGroupAddon" id="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter Your Password" name="password" required = "required">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text" id="btnGroupAddon">
                                            <label for="forus" style="cursor: pointer; margin-bottom: 0;">
                                                <span id="icon-visibility" class="material-icons" onclick="myFunction()">
                                                    visibility_off
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="m-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="mainButton form-control" style="padding: 0;">
                                        {{ __('Login') }}
                                    </button>

                                    @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                    @endif
                                    <p class="mt-1">Don't Have an Account?
                                        <a href="{{ __('register') }}">Sign up</a>
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-5">
                        <img src="img/coverr.jpeg" alt="...">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function myFunction() {
        var x = document.getElementById("password");
        if (x.type === "password") {
            x.type = "text";
            document.getElementById('icon-visibility').innerHTML = 'visibility';
        } else {
            x.type = "password";
            document.getElementById('icon-visibility').innerHTML = 'visibility_off';
        }
    }
</script>
