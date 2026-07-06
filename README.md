# Integrated Resource Inventory and Mapping System for Region V (IRIMS-V)

IRIMS-V is a web-based platform for managing, monitoring, and reporting learning resources across regional, division, district, and school stations. It maintains print and non-print resource inventories, school population and grade-offering data, library information, and user access in one system.

## Core features

- Hierarchical station management for regions, divisions, districts, and schools
- Print and non-print learning-resource inventories and masterlists
- Acquisition, title, author, package, subject, grade-level, and curriculum records
- School population and grade-offering management, including SF6 imports
- Learning-resource availability, ratio, sufficiency/excess-deficit, heatmap, and BOSY dashboards
- Normative Entitlement Computation (NEC) and resource-requirement calculations
- Spreadsheet exports and print-resource verification logs
- Role- and station-aware user management
- School, division, district, and region profile management

## Technology stack

- PHP 8.2+
- Laravel 12
- SQLite by default (other Laravel-supported databases can be configured)
- Vite 7 and Tailwind CSS 4
- ECharts 6 for dashboard visualizations
- Preline UI
- PhpSpreadsheet for imports and exports
- Pest 3 / PHPUnit for automated tests

## Local setup

### Prerequisites

Install PHP 8.2 or newer, Composer, and Node.js with npm. Enable the PHP extensions required by Laravel and the selected database driver (for the default configuration, SQLite is required).

### Installation

```bash
git clone <repository-url>
cd IRIMS-V
composer run setup
```

The setup script installs PHP and JavaScript dependencies, creates `.env` from `.env.example`, generates an application key, runs migrations, and builds the frontend assets.

If the SQLite database file does not already exist, create `database/database.sqlite`, then run:

```bash
php artisan migrate
```

To load the development seed data:

```bash
php artisan db:seed
```

Review `.env` before starting the application. At minimum, set `APP_NAME`, `APP_URL`, and the appropriate `DB_*` values for your environment. Never commit secrets or a populated `.env` file.

## Development

Start the application server, queue listener, and Vite development server together:

```bash
composer run dev
```

The application is available at `http://127.0.0.1:8000` unless configured otherwise.

For separate processes, use:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

## Testing and code quality

Run the complete automated test suite:

```bash
composer test
```

Format PHP source files with Laravel Pint:

```bash
./vendor/bin/pint
```

On Windows PowerShell, use `vendor\\bin\\pint` if the shell does not resolve the Unix-style command.

## Production build

Compile optimized frontend assets with:

```bash
npm run build
```

For production, disable debug mode, configure the production database, mail, cache, session, queue, and filesystem services, and run Laravel's deployment commands as appropriate for the hosting environment.

## Project structure

```text
app/                 Application models, controllers, services, middleware, and observers
config/              Laravel and service configuration
database/            Migrations, factories, and seeders
public/              Public entry point and static assets
resources/           Blade views, JavaScript modules, and CSS
routes/              Authentication, dashboard, resource, export, station, and user routes
tests/               Pest unit and feature tests
```

## Data and security notes

- Treat learner population, station details, user accounts, and resource records as operational data and protect them accordingly.
- Use least-privilege accounts and verify station assignments before granting access.
- Back up the database and uploaded assets before migrations or deployments.
- Do not use seeded test accounts in production.

## License

This repository does not currently declare a project-specific license. Contact the project owner before copying, redistributing, or deploying it outside its authorized environment.
