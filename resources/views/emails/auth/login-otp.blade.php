@extends('emails.layout')

@section('title', 'Login OTP')
@section('header-sub', 'Sign-In Verification')

@section('content')
    <h2>Login OTP</h2>
    <p>Hi <strong>{{ $name }}</strong>,</p>
    <p>Use the one-time password below to sign in to your account.</p>

    <div class="otp-card">
        <div class="otp-label">Verification Code</div>
        <div class="otp-value">{{ $otp }}</div>
    </div>

    <div class="note-box">
        <p><strong>Expires in:</strong> {{ $expiryMinutes }} minutes</p>
        <p class="muted" style="margin-bottom:0;">For your security, do not share this code with anyone.</p>
    </div>

    <hr class="divider">
    <p class="muted" style="margin-bottom:0;">If you did not try to sign in, you can safely ignore this email.</p>
@endsection
