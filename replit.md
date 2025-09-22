# Ticket Management System

## Overview
A PHP-based ticket management system with project tracking capabilities. The system allows users to create tickets, manage projects, assign tasks, and handle email communications. Successfully migrated from MySQL to PostgreSQL and configured for Replit environment.

## Recent Changes
- **2025-09-22**: Completed GitHub import and Replit environment setup
  - Migrated from MySQL to PostgreSQL database
  - Updated database connection configuration for Replit PostgreSQL
  - Fixed DB.php model to use PostgreSQL DSN
  - Created all required database tables with proper foreign key constraints
  - Created admin user (admin@demo.com / Password123!)
  - Set up PHP development server on port 5000
  - Configured deployment for production using autoscale

## Project Architecture
- **Language**: PHP 8.2 with Composer dependency management
- **Database**: PostgreSQL (Replit hosted)
- **Frontend**: Server-side rendered PHP views with Bootstrap 5 styling
- **Structure**: MVC-like pattern with models, views, and controllers
- **Dependencies**: PHPMailer for email functionality

### Database Tables
- `users` - User accounts with roles (admin/user)
- `departments` - Organization departments
- `groups` - User groups for permissions
- `user_groups` - Many-to-many relationship between users and groups
- `tickets` - Support tickets with status tracking
- `projects` - Projects created from tickets
- `tasks` - Project tasks with assignments
- `attachments` - File uploads for tickets
- `ticket_messages` - Conversation history for tickets
- `password_resets` - Password reset tokens

### Key Features
- User authentication and role-based access
- Ticket creation and management
- Project and task tracking
- File attachments
- Email integration (SMTP/IMAP)
- Admin user management
- Department and group organization

## Configuration
- **Database**: Uses environment variables (PGHOST, PGDATABASE, PGUSER, PGPASSWORD)
- **File Uploads**: Stored in `/attachments` directory
- **Development Server**: PHP built-in server on 0.0.0.0:5000
- **Production**: Configured for autoscale deployment

## Default Credentials
- **Admin User**: admin@demo.com
- **Password**: Password123! (should be changed immediately)

## Development Setup
The application is ready to run with the configured workflow. All database tables are created and the admin user is set up for immediate use.