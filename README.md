# Laravel ACL Boilerplate

A Laravel 12 boilerplate featuring Role-Based Access Control (RBAC), Laravel Fortify, Inertia.js/Vue.js, Wayfinder, and comprehensive ACL management with LGPD compliance considerations.

## System Requirements

- PHP >= 8.3
- Composer >= 2.0
- Node.js >= 18.x
- MySQL >= 5.7 (or MariaDB equivalent)
- Redis (optional, for caching/queuing)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/laravel-acl-boilerplate.git
cd laravel-acl-boilerplate
```

### 2. Install Dependencies

#### PHP Dependencies

```bash
composer install
```

#### Node Dependencies

```bash
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` to configure your database connection:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_acl
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This command will:

- Create all database tables
- Seed roles, permissions, modules, and menu groups
- Initialize the first term version
- Configure the access control lists (ACL)

> **Important**: No default users are seeded for security reasons. You **must** create the first administrator user manually via Laravel Tinker (see next step).

### 6. Create the First Administrator User

Since the system doesn't seed any users by default (for security), you need to create the initial admin user via Laravel Tinker using the exact command below:

```bash
php artisan tinker
```

Once in the Tinker REPL, execute:

```php
User::create([
    'name' => 'Master',
    'email' => 'master@master.com',
    'password' => bcrypt('123'),
    'role_id' => 1
]);
exit
```

> ⚠️ **Critical Security Warning**:
>
> - **Replace `'123'` with a strong, unique password** in production environments
> - The `role_id` value assumes role ID 1 corresponds to the 'Master' role. To confirm role mappings, run in Tinker:
>     ```php
>     \App\Models\Role::pluck('name', 'id')->toArray();
>     ```
>     or check the `roles` table directly after seeding.
> - **Never commit this password to version control** - ensure your `.env` is in `.gitignore`

### 7. Prepare Frontend Assets

```bash
npm run dev
```

### 8. Start the Development Server

```bash
php artisan serve
```

Access the application at `http://127.0.0.1:8000` and log in with the credentials you just created.

## Platform-Specific Instructions

Laravel's command-line interface works identically across all major operating systems. Minor notes:

- **Windows**: All commands work in Command Prompt, PowerShell, or Windows Terminal. For a Bash-like experience, consider [Windows Subsystem for Linux (WSL)](https://learn.microsoft.com/en-us/windows/wsl/install).
- **Linux/macOS**: Commands work as shown in any terminal (Bash, Zsh, etc.). Prefix with `sudo` only if encountering permission issues (rare with proper directory ownership).

## Testing the Application

Run the full test suite:

```bash
php artisan test
```

## Production Deployment Notes

When preparing for production:

1. **Environment Configuration**

    ```dotenv
    APP_ENV=production
    APP_DEBUG=false
    ```

2. **Web Server**
    - Use Nginx or Apache instead of `php artisan serve`
    - Configure proper virtual hosts pointing to the `public` directory

3. **Queue Workers** (if using queues)

    ```bash
    php artisan queue:work --sleep=3 --tries=3
    ```

    Consider using Supervisor, systemd, or similar process managers for production

4. **Caching**

    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

5. **Optimize Composer Autoloader**
    ```bash
    composer optimize-autoloader
    ```

## Project Structure Highlights

- `app/Http/Controllers/` - HTTP controllers following Controller → Service → Model pattern
- `app/Services/` - Business logic encapsulation (e.g., `SettingService`, `UserService`)
- `resources/js/pages/` - Inertia.js/Vue.js page components
- `resources/js/components/` - Reusable Vue components
- `database/seeders/` - Database seeding logic (RoleSeeder, PermissionSeeder, etc.)
- `routes/` - Web and API route definitions (including `settings.php` for system configuration)

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Troubleshooting

### Common Issues

**"Access denied for user" errors**

- Verify `.env` database credentials
- Ensure MySQL/MariaDB service is running
- Check that the database specified in `.env` exists

**"Class not found" errors after installing packages**

- Run `composer dump-autoload` to regenerate the composer autoloader

**Missing CSS/JavaScript in browser**

- Ensure you've run `npm run dev` or `npm run watch`
- Check browser console for asset loading errors

**Authentication redirect loops**

- Verify `APP_URL` in `.env` matches your actual domain/port
- Check session and cookie configuration in `config/session.php`

## Support

For questions or issues, please refer to the [Laravel Documentation](https://laravel.com/docs) or open an issue in the project repository.
