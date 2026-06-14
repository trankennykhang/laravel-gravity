<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - Gravity</title>
    <link rel="stylesheet" href="{{ asset('css/gravity.css') }}">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            animation: floatUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes floatUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-glow);
            font-weight: 700;
            color: #080c14;
            font-size: 24px;
            margin-bottom: 15px;
            font-family: var(--font-heading);
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            font-size: 13px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .checkbox-input {
            accent-color: var(--primary);
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Success Flash Notification Toast -->
    <div class="toast-container">
        @if(session('success'))
            <div class="toast" style="border-left-color: var(--success); position: static;">
                <div>
                    <strong style="color: var(--success); display: block; font-size: 13px; margin-bottom: 2px;">Success</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        @endif
    </div>

    <div class="login-container">
        <div class="login-box glass-card">
            <div class="login-header">
                <div class="login-logo">G</div>
                <h2 style="font-family: var(--font-heading); font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">Access Gateway</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Enter credentials to open secure links</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <div class="gravity-form-grid" style="grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
                    <!-- Email input -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            value="{{ old('email') }}" 
                            class="form-input @error('email') is-invalid @enderror" 
                            placeholder="admin@gravity.com"
                            required 
                            autofocus
                        >
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password input -->
                    <div class="form-group">
                        <label for="password" class="form-label">Security Key (Password)</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-input @error('password') is-invalid @enderror" 
                            placeholder="••••••••"
                            required
                        >
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="remember-forgot" style="margin-bottom: 25px;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" class="checkbox-input">
                        <span>Remember credentials</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Establish Link
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto remove toast alerts
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
