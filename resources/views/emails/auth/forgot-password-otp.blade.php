@extends('emails.layout')

@section('title', 'Password Reset OTP')
@section('header-sub', 'Password Reset')

@section('content')
    <h2>Reset Your Password</h2>
    <p>Hi <strong>{{ $name }}</strong>,</p>
    <p>We received a request to reset your account password. Use the one-time password below to proceed.</p>

    <div class="otp-card warm">
        <div class="otp-label">Password Reset Code</div>
        <div class="otp-value">{{ $otp }}</div>
    </div>

    <div class="note-box">
        <p><strong>Expires in:</strong> {{ $expiryMinutes }} minutes</p>
        <p class="muted" style="margin-bottom:0;">For your security, never share this code with anyone.</p>
    </div>

    <hr class="divider">
    <p class="muted" style="margin-bottom:0;">If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
@endsection
