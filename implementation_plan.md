# Implementation Plan - User Management & Authentication

We will extend the Laravel Gravity application to include fully featured user authentication and user management capabilities. 

To keep the application entirely self-contained (working without system-level database drivers), we will build a custom **Laravel Auth User Provider** backed by our JSON database. This will allow standard Laravel auth middleware, sessions, and guards to work flawlessly. We will also utilize our dynamic `Datatable` and `FormBuilder` to build a complete **User Management Dashboard**.

---

## User Review Required

> [!IMPORTANT]
> **Key Architecture Decisions:**
> 1. **Custom JSON User Provider:** We will implement the `UserProvider` and `Authenticatable` contracts, registering a custom `json` auth provider in `AppServiceProvider`. This enables standard Laravel routing filters (`->middleware('auth')`) to work flawlessly with our JSON-based users.
> 2. **Authentication Gate:** The Product Catalog dashboard and all edit/delete actions will be protected by standard Laravel session authentication. Unauthenticated requests will redirect to a highly polished login page.
> 3. **Demonstrating Extensibility:** We will add a **User Management** route and dashboard. Because we built a dynamic engine, we will render a fully functioning User list (with search, sort, and paging) and User Create/Edit forms using our `Datatable` and `FormBuilder` in under 30 lines of code!

---

## Proposed Changes

### 1. Authenticatable JSON User System

#### [NEW] [User.php](file:///home/kenny/www/php/laravel-gravity/app/Models/User.php)
- Extends custom `JsonModel` and implements `Illuminate\Contracts\Auth\Authenticatable`.
- Fields: `id`, `name`, `email`, `password` (hashed), `remember_token`, `created_at`, `updated_at`.

#### [NEW] [JsonUserProvider.php](file:///home/kenny/www/php/laravel-gravity/app/Gravity/Auth/JsonUserProvider.php)
- Implements `Illuminate\Contracts\Auth\UserProvider` to load and validate users using our JSON database structure and `Hash::check`.

#### [MODIFY] [AppServiceProvider.php](file:///home/kenny/www/php/laravel-gravity/app/Providers/AppServiceProvider.php)
- Registers the custom `json` user provider in Laravel's `Auth` gate:
  ```php
  Auth::provider('json', function ($app, array $config) {
      return new \App\Gravity\Auth\JsonUserProvider();
  });
  ```

#### [MODIFY] [auth.php](file:///home/kenny/www/php/laravel-gravity/config/auth.php)
- Set user provider driver to `json` and model to `App\Models\User`.

#### [NEW] [UserSeeder.php](file:///home/kenny/www/php/laravel-gravity/database/seeders/UserSeeder.php)
- Seeds a default administrative user: `admin@gravity.com` with password `password`.

---

### 2. Login Page & Controller

#### [NEW] [AuthController.php](file:///home/kenny/www/php/laravel-gravity/app/Http/Controllers/AuthController.php)
- Methods:
  - `showLogin`: Displays the login page.
  - `login`: Performs request validation and `Auth::attempt()`.
  - `logout`: Logs the user out and clears session.

#### [NEW] [login.blade.php](file:///home/kenny/www/php/laravel-gravity/resources/views/gravity/login.blade.php)
- Stunning, premium glassmorphic login screen featuring glowing blue/cyan outlines, custom user icon visuals, and slide-in hover transitions.

---

### 3. User Management Interface

#### [NEW] [UserController.php](file:///home/kenny/www/php/laravel-gravity/app/Http/Controllers/UserController.php)
- Fully implements CRUD for users leveraging the `Datatable` and `FormBuilder` frameworks.
- Protects password editing (hashed securely upon save).

#### [MODIFY] [layout.blade.php](file:///home/kenny/www/php/laravel-gravity/resources/views/gravity/layout.blade.php)
- Adds a dynamic sidebar/navbar including navigation links: **Products Dashboard**, **User Management**, and a **Logout** button for authenticated users.

#### [MODIFY] [web.php](file:///home/kenny/www/php/laravel-gravity/routes/web.php)
- Sets up `/login` and `/logout` routes.
- Groups all CRUD routes (`products` and `users`) under the standard Laravel `auth` middleware.

---

## Verification Plan

### Automated & Manual Verification
- We will start the dev server, verify that attempting to visit `/` redirects instantly to `/login`.
- Verify the aesthetics of the Login screen.
- Test validation: Submit login form empty, check for validation errors.
- Test authentication: Log in with invalid credentials, check for invalid login alerts. Log in successfully with `admin@gravity.com` and `password`.
- Verify redirection to the dashboard and the dynamic navbar options showing logged-in user context.
- Navigate to **User Management** and test:
  1. Creating a new user via the dynamic form generator.
  2. Modifying a user name or email.
  3. Sorting users by Name or Email and paging through results.
- Log out and verify redirection back to `/login`, with subsequent page-loads of `/` blocked correctly.
