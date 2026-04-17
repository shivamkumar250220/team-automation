<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Login') — {{ config('app.name', 'AdminPanel') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    </head>
    <body class="auth-body">

        <div class="auth-wrapper">
            <div class="auth-inner">
                <div class="auth-logo">
            <p>Welcome back! Sign in to your account.</p>
        </div>
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
        
            @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-3">
                <i class="mdi mdi-alert-circle me-1"></i>
                {{ $errors->first() }}
            </div>
            @endif
        
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                    <input type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required autofocus>
                </div>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        
            <div class="form-group">
                <label class="form-label d-flex justify-content-between">
                    <span>Password</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="mdi mdi-lock-outline"></i></span>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Enter password" id="loginPassword" required>
                    <button class="btn btn-light btn-icon" style="padding: 23px" type="button" onclick="togglePwd('loginPassword')">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">Keep me signed in</label>
                </div>
            </div>
        
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="mdi mdi-login me-1"></i>Sign In
            </button>
        
            <div class="auth-divider">or continue with</div>
        
        </form>
        

        <script>
        function togglePwd(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        </script>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>