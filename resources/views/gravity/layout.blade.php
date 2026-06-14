<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laravel Gravity')</title>
    <link rel="stylesheet" href="{{ asset('css/gravity.css') }}">
    <style>
        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-left: 35px;
        }
        .nav-link-item {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            font-family: var(--font-heading);
            letter-spacing: 0.2px;
        }
        .nav-link-item:hover, .nav-link-item.active {
            color: var(--primary);
            text-shadow: 0 0 8px var(--primary-glow);
        }
        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 13px;
        }
        @media(max-width: 768px) {
            .app-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 20px;
            }
            .app-header > div {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .nav-links {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Dynamic Toast Alerts -->
    <div class="toast-container">
        @if(session('success'))
            <div class="toast" style="border-left-color: var(--success);">
                <div>
                    <strong style="color: var(--success); display: block; font-size: 13px; margin-bottom: 2px;">Success</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="toast" style="border-left-color: var(--danger);">
                <div>
                    <strong style="color: var(--danger); display: block; font-size: 13px; margin-bottom: 2px;">Error</strong>
                    <span>{{ session('error') }}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        @endif
    </div>

    <div class="gravity-app">
        <!-- Main Header -->
        <header class="app-header">
            <div style="display: flex; align-items: center;">
                <div class="brand">
                    <div class="brand-logo">G</div>
                    <div>
                        <h1 class="brand-title">Laravel Gravity</h1>
                        <p style="color: var(--text-muted); font-size: 12px; margin-top: 2px;">Dynamic Datatables & Form Engine</p>
                    </div>
                </div>
                
                @if(Auth::check())
                    <nav class="nav-links">
                        <a href="{{ route('products.index') }}" class="nav-link-item {{ Request::is('products*') || Request::is('/') ? 'active' : '' }}">Products</a>
                        <a href="{{ route('users.index') }}" class="nav-link-item {{ Request::is('users*') ? 'active' : '' }}">Staff Directory</a>
                    </nav>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                @if(Auth::check())
                    <div class="user-profile-badge">
                        <span style="width: 8px; height: 8px; background: var(--success); border-radius: 50%; display: inline-block;"></span>
                        <span style="color: var(--text-secondary);">Secure Link: <strong>{{ Auth::user()->name }}</strong></span>
                    </div>
                    
                    @yield('header_actions')

                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-secondary">
                        Terminate Session
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @else
                    @yield('header_actions')
                @endif
            </div>
        </header>

        <!-- Main Body -->
        <main>
            @yield('content')
        </main>
    </div>

    <!-- Micro-interaction JS scripts -->
    <script>
        // Auto remove toast alerts after 5 seconds
        document.querySelectorAll('.toast').forEach(toast => {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.5s ease';
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>
