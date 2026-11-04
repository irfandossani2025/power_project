<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PowerBooks') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/powerbooks.css') }}">
    <link rel="stylesheet" href="{{ asset('css/powerbooks-extra.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><b>P</b><span>Power<span>Books</span></span></a>
        <div class="company-switcher"><i></i>{{ $company->name ?? 'My Business' }}<em>⌄</em></div>
        <nav>
            <p>WORKSPACE</p>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span><span>Dashboard</span></a>
            <a class="{{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="m8 9 4 3 4-3M8 16h8"/></svg></span><span>Quotations</span></a>
            <a class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v5h5M9 13h6M9 17h6"/></svg></span><span>Sales invoices</span></a>
            <a class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4z"/><path d="M8 7V4h8v3M8 13h8M8 17h5"/></svg></span><span>Purchases</span></a>
            <a class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 3h14v18H5z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span><span>Expenses</span></a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 20V4M4 20h17"/><path d="m7 16 4-5 3 3 5-7"/></svg></span><span>Reports</span></a>
            @if(in_array(auth()->user()->role,['admin','accountant']))
                <a class="{{ request()->routeIs('ledger.*') ? 'active' : '' }}" href="{{ route('ledger.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></span><span>Accounting ledger</span></a>
            @endif
            <a class="{{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 3v13M8 7l4-4 4 4M12 21V8M16 17l-4 4-4-4"/></svg></span><span>Import data</span></a>
            <p style="margin-top:18px">DIRECTORY</p>
            <a class="{{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c.8-4 3.2-6 7-6s6.2 2 7 6"/></svg></span><span>Customers</span></a>
            <a class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c.7-4 2.7-6 6-6 1.1 0 2 .2 2.8.7M16 8h5M18.5 5.5v5"/></svg></span><span>Suppliers</span></a>
            <a class="{{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="m4 7 8-4 8 4-8 4zM4 7v10l8 4 8-4V7M12 11v10"/></svg></span><span>Items</span></a>
            <a class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 8h18v12H3zM7 8V5h10v3M7 13h10M7 17h6"/></svg></span><span>Inventory</span></a>
            <a class="{{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 20V5h16v15H4zM8 9h8M8 13h5M8 20v-3h3v3M16 20v-5h2v5"/></svg></span><span>Projects</span></a>
        </nav>
        @if(auth()->user()->role==='admin')
            <section class="admin-links" aria-label="Administration">
                <p>ADMIN</p>
                <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3 20c.7-4 2.7-6 6-6s5.3 2 6 6M15 15c3 0 5 1.7 5.7 5"/></svg></span><span>Team access</span></a>
                <a class="{{ request()->routeIs('settings.company.*') ? 'active' : '' }}" href="{{ route('settings.company.edit') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h6M9 14h6M10 21v-3h4v3"/></svg></span><span>Business settings</span></a>
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
<script src="{{ asset('js/powerbooks.js') }}"></script>
</body>
</html>
