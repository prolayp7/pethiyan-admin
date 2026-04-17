@extends('emails.layout')

@section('title', 'Registration OTP')
@section('header-sub', 'Account Verification')

@section('content')
    <h2>Registration OTP</h2>
    <p>Hi <strong>{{ $name }}</strong>,</p>
    <p>Use the one-time password below to complete your registration.</p>

    <div style="margin:20px 0; padding:18px 20px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; text-align:center;">
        <div style="font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#1d4ed8; margin-bottom:8px;">Verification Code</div>
        <div style="font-size:32px; font-weight:800; letter-spacing:8px; color:#111827;">{{ $otp }}</div>
    </div>

    <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px 18px; margin:20px 0;">
        <p style="margin:0 0 8px;"><strong>Expires in:</strong> {{ $expiryMinutes }} minutes</p>
        <p style="margin:0; color:#6b7280; font-size:13px;">For your security, do not share this code with anyone.</p>
    </div>

    <hr class="divider">
    <p style="color:#6b7280; font-size:13px; margin-bottom:0;">If you did not try to create an account, you can safely ignore this email.</p>
@endsection