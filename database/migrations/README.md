# Database Migrations

This directory contains migration scripts to update the database schema.

## Applying Migrations

To apply the migration that adds the `society_id` column to the `users` table:

1. Execute the SQL script `add_society_id_to_users.sql` in your MySQL database
2. This can be done through phpMyAdmin or the MySQL command line

## Migration Details

### add_society_id_to_users.sql
- Adds the missing `society_id` column to the `users` table
- Adds a foreign key constraint to reference the `societies` table
- Fixes the registration error where the column was not found