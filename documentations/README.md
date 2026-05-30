# MyGate Backend - FULL VERSION

A comprehensive, scalable backend solution for the MyGate property management system, implementing all features from the official MyGate offerings.

## Features Implemented

This backend covers all the offerings mentioned on the MyGate website:

1. **Accounting ERP** - Complete accounting system with chart of accounts, invoicing, payments, and financial reporting
2. **Payment Infrastructure** - Multi-mode payment processing with various gateways
3. **Communications** - Group messaging, announcements, polls, and meeting management
4. **Amenities Module** - Booking and management of society amenities
5. **Digital Helpdesk** - Ticketing system for resident issues
6. **Asset & Inventory Management** - Tracking of society assets and inventory
7. **Security Features** - Resident, admin, and guard security features
8. **Vehicle & Parking Management** - Vehicle registration and parking allocation
9. **Admin Dashboard** - Comprehensive admin controls
10. **Admin Roles & Access Control** - Role-based permissions system
11. **Pet Directory** - Pet registration and management
12. **Marketplace & eCommerce** - Community marketplace functionality
13. **Integrations & Partnerships** - Extensible integration framework
14. **Data Security and Privacy** - Audit trails and privacy controls
15. **Interfaces** - RESTful API interfaces
16. **Services & Trainings** - Service booking and management
17. **COVID Safety Features** - Vaccination and testing tracking
18. **Enhanced Registration Flow** - Live search societies, building selection, and flat lookup for fast mobile registration

1. **Accounting ERP** - Complete accounting system with chart of accounts, invoicing, payments, and financial reporting
2. **Payment Infrastructure** - Multi-mode payment processing with various gateways
3. **Communications** - Group messaging, announcements, polls, and meeting management
4. **Amenities Module** - Booking and management of society amenities
5. **Digital Helpdesk** - Ticketing system for resident issues
6. **Asset & Inventory Management** - Tracking of society assets and inventory
7. **Security Features** - Resident, admin, and guard security features
8. **Vehicle & Parking Management** - Vehicle registration and parking allocation
9. **Admin Dashboard** - Comprehensive admin controls
10. **Admin Roles & Access Control** - Role-based permissions system
11. **Pet Directory** - Pet registration and management
12. **Marketplace & eCommerce** - Community marketplace functionality
13. **Integrations & Partnerships** - Extensible integration framework
14. **Data Security and Privacy** - Audit trails and privacy controls
15. **Interfaces** - RESTful API interfaces
16. **Services & Trainings** - Service booking and management
17. **COVID Safety Features** - Vaccination and testing tracking

## Technology Stack

- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: JWT (JSON Web Tokens)
- **Architecture**: MVC (Model-View-Controller)
- **API Style**: RESTful

## Installation with XAMPP

1. Clone or copy the project to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\mygate-backend-FULL
   ```

2. Start XAMPP services:
   - Apache
   - MySQL

3. Open phpMyAdmin at http://localhost/phpmyadmin

4. Create the database:
   - Click "New" in the left sidebar
   - Enter database name: `migate`
   - Click "Create"

5. Import the database schema:
   - Select the `migate` database
   - Click "Import" tab
   - Choose `database/migate.sql` file
   - Click "Go"

6. Configure environment variables:
   The `.env` file is already configured for XAMPP defaults:
   ```env
   DB_HOST=localhost
   DB_NAME=migate
   DB_USER=root
   DB_PASS=
   JWT_SECRET=supersecretkeyformygate
   ```

7. Test the installation:
   Open your browser and navigate to:
   - http://localhost/mygate-backend-FULL/public/ - Main test page
   - http://localhost/mygate-backend-FULL/public/api/test - API test endpoint
   - http://localhost/mygate-backend-FULL/public/api/health - Health check endpoint

## API Documentation

Comprehensive API documentation is available in [API_DOCUMENTATION.md](file:///s:/MyGate/mymygate-backend-FULL/API_DOCUMENTATION.md)

## Project Structure

```
mygate-backend-FULL/
├── app/                    # Application source code
│   ├── config/            # Configuration files
│   ├── core/              # Core framework components
│   ├── helpers/           # Helper functions
│   ├── middleware/        # Middleware components
│   ├── modules/           # Feature modules
│   └── routes/            # API route definitions
├── database/              # Database schema and migrations
├── public/                # Publicly accessible files
├── tests/                 # Test files
├── vendor/                # Composer dependencies
├── .env                   # Environment configuration
├── composer.json          # Composer configuration
└── README.md             # This file
```

## Modules

Each feature area is implemented as a separate module:

- **Auth** - User authentication and authorization
- **Admin** - Administrative functions and society management
- **Accounting** - Financial management and billing
- **Communications** - Messaging and announcements
- **Amenities** - Facility booking and management
- **Helpdesk** - Ticketing system
- **Security** - Security alerts and emergency contacts
- **Vehicles** - Vehicle and parking management

## Testing

Run the basic tests by accessing these URLs in your browser:
- http://localhost/mygate-backend-FULL/public/ - Main test interface
- http://localhost/mygate-backend-FULL/public/api/test - Simple API test
- http://localhost/mygate-backend-FULL/public/api/health - Health check
- http://localhost/mygate-backend-FULL/public/db_test.php - Database connectivity test

## Security

- All API endpoints are protected with JWT authentication
- Role-based access control for different user types
- Input validation and sanitization
- SQL injection prevention through prepared statements
- XSS prevention through output encoding

## Performance Considerations

- Database indexes for frequently queried columns
- Pagination for large dataset retrieval
- Efficient query design
- Connection pooling through PDO

## Scalability Features

- Modular architecture for easy feature additions
- Database normalization for data integrity
- Caching-ready design
- Horizontal scaling support

## Troubleshooting

### Common Issues with XAMPP:

1. **Database Connection Failed**:
   - Ensure MySQL service is running in XAMPP
   - Check that the database `migate` exists
   - Verify `.env` configuration matches your XAMPP setup

2. **404 Errors**:
   - Make sure mod_rewrite is enabled in Apache
   - Check that your virtual host configuration allows .htaccess overrides

3. **Permission Issues**:
   - Ensure the project directory has proper read permissions
   - Check that the `vendor` directory exists and has proper permissions

### Enabling mod_rewrite in XAMPP:

1. Open `httpd.conf` in XAMPP Apache configuration
2. Find `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Remove the `#` to uncomment the line
4. Restart Apache

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is proprietary and confidential. All rights reserved.

## Support

For support, please contact the development team.