@extends('layouts.admin.guest')

@section('title', __('labels.forgot_password'))
@section('content')
    <div>
        <div class="page page-center admin-login-theme">
            <div class="container container-tight py-4">
                <div class="text-center mb-4 admin-login-brand-wrap">
                    <!-- BEGIN NAVBAR LOGO -->
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="{{ !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo.png') }}"
                             alt="{{ $systemSettings['appName'] ?? '' }}" width="150px">
                    </a>
                    <!-- END NAVBAR LOGO -->
                    <p class="admin-login-subtitle mb-0 mt-2">Account recovery for {{ $systemSettings['appName'] ?? config('app.name') }}</p>
                </div>
                <div class="card card-md admin-login-card">
                    <div class="card-body">
                        <h2 class="h2 text-center mb-1">Forgot your password?</h2>
                        <p class="text-center admin-login-caption mb-4">Enter your email and we’ll send a secure reset link.</p>

                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form action="{{route('admin.password.email')}}" method="post" autocomplete="off" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       name="email" placeholder="your@email.com" value="{{ old('email') }}"
                                       autocomplete="off" required/>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary w-100">Send password reset link</button>
                            </div>
                        </form>
                        <div class="text-center text-muted mt-3">
                            Remember your password? <a href="{{route('admin.login')}}">Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
