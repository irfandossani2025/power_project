<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PowerBooks') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/powerbooks.css') }}"><link rel="stylesheet" href="{{ asset('css/powerbooks-extra.css') }}"><link rel="stylesheet" href="{{ asset('css/powerbooks-auth.css') }}">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-story">
            <a href="{{ url('/') }}" class="auth-brand"><b>P</b><span>Power<span>Books</span></span></a>
            <div class="auth-copy"><p>ACCOUNTING · INVENTORY · QUOTATIONS</p><h1>Run the day.<br>See the whole business.</h1><span>Clear numbers, professional quotations, and a calm workspace for your team.</span></div>
            <div class="auth-proof"><i>✓</i><span><b>Built for Oman</b><small>OMR and VAT-ready from day one</small></span></div>
        </section>
        <section class="auth-panel"><div class="auth-card"><a href="{{ url('/') }}" class="auth-mobile-brand"><b>P</b> Power<span>Books</span></a>{{ $slot }}</div><p class="auth-footer">© {{ now()->year }} PowerBooks · Business clarity made simple</p></section>
    </main>
</body>
</html>
