# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Frontend (Node.js/Vue.js)
- `npm run dev` - Start Vite development server
- `npm run build` - Build production assets
- `npm run build:ssr` - Build with server-side rendering
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting

### Backend (Laravel/PHP)
- `composer run dev` - Start all development services (server, queue, vite)
- `composer run dev:ssr` - Start development with SSR enabled
- `composer run test` - Run PHPUnit tests
- `php artisan serve` - Start Laravel development server
- `php artisan test` - Run application tests
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with test data

## Architecture Overview

### Technology Stack
- **Backend**: Laravel 12 (PHP 8.2+) with Inertia.js
- **Frontend**: Vue 3 + TypeScript with Vite
- **Database**: MySQL/SQLite with Eloquent ORM
- **Styling**: TailwindCSS v4 with Reka UI components
- **Icons**: Lucide Vue Next
- **Charts**: Chart.js with date-fns adapter

### Role-Based Access Control System
The application implements a three-tier role system:

**Roles:**
- `super_admin` - Full CRUD access to all resources
- `admin` - Read-only access to all resources  
- `marketing` - CRUD access only to their own Mitra records

**Key Components:**
- `HasRoleAccess` trait (app/Traits/HasRoleAccess.php) - Core role logic
- `RoleBasedAccess` middleware (app/Http/Middleware/RoleBasedAccess.php) - Route protection
- `role.access:{permission}` middleware groups in routes for granular permissions

### Core Entities and Relationships

**Users** - Authentication and role management
- Uses `HasRoleAccess` trait for permission checks
- Relations: `mitras()`, `todoLists()`, `assignedTodoLists()`

**Mitras** - Primary business entity (marketing partners)
- Belongs to User (marketing role)
- Associated with Brand and Label
- Filterable by user role (marketing sees only own records)

**Brands** - Product brands with logo management
- Has many Mitras
- Logo stored with `logo_url` accessor

**Labels** - Categorization system for Mitras
- Used for organizing and filtering Mitra records

**TodoLists** - Task management system
- Supports assignment between users
- Calendar view integration

### Frontend Architecture

**Page Structure:**
- `resources/js/pages/` - Inertia.js pages organized by feature
- `resources/js/components/` - Reusable Vue components
- `resources/js/components/ui/` - UI component library (Reka UI based)

**Key Patterns:**
- Modal-based CRUD operations (see BrandModal.vue, MitraModal.vue, UserModal.vue)
- Consistent delete confirmation modals
- Chart components for dashboard analytics
- Role-based UI element hiding (admin role restrictions)

### Route Organization
- `routes/web.php` - Main application routes with role-based middleware
- `routes/auth.php` - Authentication routes
- `routes/settings.php` - User settings and site configuration

### Database Architecture
- Uses standard Laravel migrations in `database/migrations/`
- Comprehensive seeders for development data
- Factory classes for testing data generation

## Development Notes

### Excel Export/Import
- Uses `phpoffice/phpspreadsheet` for Mitra data export/import
- Template download available at `/mitras/template`
- Export functionality respects role-based data filtering

### Theme System
- Light/dark mode support via `useAppearance` composable
- Theme state persisted across sessions
- Automatic initialization on page load

### Testing
- PHPUnit configured for Laravel testing
- Factory-based test data generation
- Role-based access control testing recommended

### UI Consistency
- Modal pattern established for CRUD operations
- Consistent button styles and form layouts
- Chart.js integration for analytics dashboards
- Responsive design with TailwindCSS