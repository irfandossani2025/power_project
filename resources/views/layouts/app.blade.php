<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PowerBooks') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/powerbooks.css').'?v='.filemtime(public_path('css/powerbooks.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/powerbooks-extra.css').'?v='.filemtime(public_path('css/powerbooks-extra.css')) }}">
</head>
<body>
@php($role = auth()->user()->role)
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><b>P</b><span>Power<span>Books</span></span></a>
        <div class="company-switcher"><i></i>{{ $company->name ?? 'My Business' }}<em>⌄</em></div>
        <nav>
            <p>WORKSPACE</p>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span><span>Dashboard</span></a>
            @if(in_array($role,['admin','accountant','sales']))<a class="{{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}"><span class="nav-icon">✉</span><span>Quotations</span></a><a class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><span class="nav-icon">▤</span><span>Sales invoices</span></a>@endif
            @if(in_array($role,['admin','accountant','inventory']))<a class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><span class="nav-icon">▣</span><span>Purchases</span></a>@endif
            @if(in_array($role,['admin','accountant']))<a class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span class="nav-icon">▤</span><span>Expenses</span></a><a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-icon">⌁</span><span>Reports</span></a>@endif
            @if(in_array(auth()->user()->role,['admin','accountant']))
                <a class="{{ request()->routeIs('ledger.*') ? 'active' : '' }}" href="{{ route('ledger.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></span><span>Accounting ledger</span></a>
            @endif
            @if(in_array($role,['admin','accountant']))<a class="{{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}"><span class="nav-icon">↕</span><span>Import data</span></a><a class="{{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><span class="nav-icon">♙</span><span>HRMS</span></a><a class="{{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.index') }}"><span class="nav-icon">◫</span><span>Payroll</span></a>@endif
            <p style="margin-top:18px">DIRECTORY</p>
            @if(in_array($role,['admin','accountant','sales']))<a class="{{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="nav-icon">♙</span><span>Customers</span></a>@endif
            @if(in_array($role,['admin','accountant','inventory']))<a class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><span class="nav-icon">♙</span><span>Suppliers</span></a><a class="{{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-icon">◇</span><span>Items</span></a><a class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span class="nav-icon">▣</span><span>Inventory</span></a><a class="{{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><span class="nav-icon">▱</span><span>Vehicles</span></a>@endif
            @if(in_array($role,['admin','accountant','sales','inventory']))<a class="{{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><span class="nav-icon">⌂</span><span>Projects</span></a>@endif
            <a class="{{ request()->routeIs('finance-buddy.*') ? 'active' : '' }}" href="{{ route('finance-buddy.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 3a6 6 0 0 0-6 6v3a6 6 0 0 0 12 0V9a6 6 0 0 0-6-6ZM9 10h.01M15 10h.01M9 14c1.8 1.4 4.2 1.4 6 0M12 1v2M4 6l2 1M20 6l-2 1"/></svg></span><span>Finance Buddy</span></a>
        </nav>
        @if(auth()->user()->role==='admin')
            <section class="admin-links" aria-label="Administration">
                <p>ADMIN</p>
                <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3 20c.7-4 2.7-6 6-6s5.3 2 6 6M15 15c3 0 5 1.7 5.7 5"/></svg></span><span>Team access</span></a>
                <a class="{{ request()->routeIs('settings.company.*') ? 'active' : '' }}" href="{{ route('settings.company.edit') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h6M9 14h6M10 21v-3h4v3"/></svg></span><span>Business settings</span></a>
                <a class="{{ request()->routeIs('settings.finance-buddy.*') ? 'active' : '' }}" href="{{ route('settings.finance-buddy.edit') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 3a6 6 0 0 0-6 6v3a6 6 0 0 0 12 0V9a6 6 0 0 0-6-6ZM9 10h.01M15 10h.01M9 14c1.8 1.4 4.2 1.4 6 0"/></svg></span><span>AI settings</span></a>
            </section>
        @endif
        <div class="bottom"><a href="{{ route('profile.edit') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.1 2.1-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-3v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-2.1-2.1.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H5.3v-3h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2.1-2.1.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h3v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 2.1 2.1-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v3h-.2a1.7 1.7 0 0 0-1.5 1Z"/></svg></span><span>My profile</span></a></div>
        <footer><i></i> OMR · Oman VAT ready</footer>
    </aside>
    <main>
        <header>
            <button class="mobile-menu" id="menuToggle">☰</button>
            <label class="search">⌕ <input placeholder="Search customers, quotations and items"><kbd>⌘ K</kbd></label>
            <div class="top-actions"><button class="bell">♧<i></i></button><form method="POST" action="{{ route('logout') }}">@csrf<button class="profile" title="Log out"><strong>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</strong><span><b>{{ auth()->user()->name }}</b><small>{{ ucfirst(auth()->user()->role) }} · Log out</small></span></button></form></div>
        </header>
        @if(session('success'))<div class="flash success">✓ {{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash error">Please correct the highlighted fields and try again.</div>@endif
        <section class="content">{{ $slot }}</section>
    </main>
</div>
<aside class="buddy-float" id="buddyFloat" aria-label="Finance Buddy">
    <button class="buddy-launcher" id="buddyLauncher" type="button" aria-expanded="false"><span>✦</span><b>Finance Buddy</b></button>
    <section class="buddy-drawer" aria-hidden="true">
        <header><div><i>✦</i><span><b>Finance Buddy</b><small>Role-aware · {{ ucfirst($role) }}</small></span></div><button type="button" class="buddy-close" aria-label="Close Finance Buddy">×</button></header>
        <div class="buddy-thread" id="buddyThread"><div class="buddy-float-message"><b>Finance Buddy</b><p>Hello {{ strtok(auth()->user()->name, ' ') }}. Ask about the areas you are allowed to use. I will never post, send, delete, or change records without your review.</p></div></div>
        <form class="buddy-float-form" id="buddyFloatForm" action="{{ route('finance-buddy.ask') }}" method="POST">@csrf<textarea name="prompt" maxlength="2000" required placeholder="Ask Finance Buddy…"></textarea><button class="primary" type="submit">Send</button></form>
        <a class="buddy-full-link" href="{{ route('finance-buddy.index') }}">Open full Finance Buddy workspace →</a>
    </section>
</aside>
<script src="{{ asset('js/powerbooks.js').'?v='.filemtime(public_path('js/powerbooks.js')) }}"></script>
</body>
</html>
