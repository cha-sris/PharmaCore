# PharmaCore

A comprehensive pharmacy management system for handling medicines, suppliers, alerts, and inventory tracking.

## Project Structure

```
PharmaCore/
├── assets/
│   ├── css/                    - Stylesheets for different pages and components
│   │   ├── alerts.css         - Styles for alert notifications
│   │   ├── auth.css           - Styles for login and registration pages
│   │   ├── dashboard.css      - Styles for the main dashboard
│   │   ├── medicines.css      - Styles for medicines management page
│   │   ├── search.css         - Styles for search functionality
│   │   ├── sidebar.css        - Styles for sidebar navigation
│   │   ├── suppliers.css      - Styles for suppliers management page
│   │   └── variables.css      - Global CSS variables and themes
│   │
│   └── images/                - SVG and image assets used throughout the application
│       └── (Various SVG icons for UI components, menu items, and visual indicators)
│
├── auth/                       - Authentication module
│   ├── login.php              - User login page and logic
│   ├── logout.php             - User logout functionality
│   └── register.php           - User registration page and logic
│
├── config/                     - Configuration files
│   └── config.php             - Database and application configuration
│
├── docs/                       - Project documentation and diagrams
│   ├── architecture/          - System architecture documentation
│   │   ├── er-diagram/        - Entity relationship diagram (DrawIO and PNG formats)
│   │   ├── flowchart/         - Application flowchart diagrams
│   │   ├── model/             - Development model documentation
│   │   ├── proposal-slides/   - Project proposal presentation slides
│   │   └── use-case-diagram/  - Use case diagrams for system functionality
│   │
│   └── project-management/    - Project planning and management documents
│       ├── gantt-chart.png    - Project timeline and milestones
│       └── proposal.md        - Project proposal documentation
│
├── includes/                   - Reusable PHP components
│   ├── sidebar.php            - Sidebar navigation component
│   └── validation.php         - Form validation functions
│
├── modules/                    - Main application modules
│   ├── alerts.php             - Alerts and notifications management
│   ├── dashboard.php          - Main dashboard display
│   ├── medicines.php          - Medicines inventory management
│   └── suppliers.php          - Suppliers management
│
└── README.md                   - This file
```

## Features

- **Authentication**: Secure user login and registration
- **Dashboard**: Overview of key metrics and alerts
- **Medicines Management**: Track and manage pharmaceutical inventory
- **Supplier Management**: Manage supplier information and details
- **Alerts System**: Monitor medicine expiration and stock levels
- **Search Functionality**: Quick search across medicines and suppliers

---

## Overview

PharmaCore is a web-based pharmacy management system designed to streamline pharmaceutical operations. It provides a centralized platform for managing medicine inventory, tracking supplier relationships, monitoring stock levels, and receiving critical alerts about medicine expiration and low stock conditions.

### Key Objectives

- Simplify pharmacy inventory management
- Improve supply chain efficiency
- Reduce waste through better expiration tracking
- Enhance decision-making with real-time analytics
- Ensure regulatory compliance

---

## Technology Stack

### Backend

- **Language**: PHP (Server-side logic)
- **Database**: MySQL/MariaDB (Data persistence)
- **Framework**: Core PHP (Custom architecture)

### Frontend

- **HTML5**: Semantic markup
- **CSS3**: Responsive styling with custom variables
- **JavaScript**: Dynamic interactions and validations
- **SVG Icons**: Lightweight vector graphics

### Tools & Utilities

- **DrawIO**: Architecture and diagram documentation
- **XAMPP**: Local development server (Apache, MySQL, PHP)

---

## System Requirements

### Server Requirements

- **Apache Web Server** 2.4+
- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher / MariaDB 10.3+

### Client Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 1024x768px

### Optional Requirements

- Git (for version control)
- DrawIO (for editing architecture diagrams)

---

## Installation & Setup

### Prerequisites

1. Install XAMPP or similar PHP development environment
2. Ensure Apache and MySQL services are running
3. Have administrative access to your server

### Step-by-Step Installation

1. **Clone or Download the Project**

   ```bash
   cd /opt/lampp/htdocs/my_projects/
   # Place PharmaCore folder here
   ```

2. **Configure Database**
   - Update `config/config.php` with your database credentials
   - Create necessary database tables (scripts in docs/architecture/)

3. **Access the Application**

   ```
   http://localhost/my_projects/PharmaCore/
   ```

4. **Default Login**
   - Username: admin
   - Password: (Set during initial setup)

---

## Usage Guide

### Main Modules

#### 1. Authentication Module (`auth/`)

- **Login**: Secure user authentication
- **Registration**: New user account creation
- **Logout**: Secure session termination
- **Session Management**: Automatic session timeout

#### 2. Dashboard (`modules/dashboard.php`)

- Real-time inventory overview
- Key performance indicators (KPIs)
- Recent transactions and activities
- Visual charts and statistics
- Quick access to critical functions

#### 3. Medicines Management (`modules/medicines.php`)

- Add, edit, and delete medicines
- Track medicine details (name, dosage, expiration date, quantity)
- Monitor stock levels
- View medicine suppliers
- Search and filter medicines by category

#### 4. Supplier Management (`modules/suppliers.php`)

- Manage supplier profiles and contact information
- Track supplier relationships
- View supplied medicines
- Supplier performance metrics
- Communication history

#### 5. Alerts System (`modules/alerts.php`)

- **Expiring Medicines**: Warnings for medicines nearing expiration
- **Low Stock Alerts**: Notifications for inventory below threshold
- **Critical Alerts**: High-priority notifications
- Alert management and dismissal
- Alert history and logs

#### 6. Search Functionality (`assets/css/search.css`)

- Global search across medicines and suppliers
- Advanced filtering options
- Real-time search results
- Filter by category, expiration date, stock status

---

## Core Modules Details

### Authentication System

- Password encryption and validation
- Session-based authentication
- Role-based access control
- Account security features

### Inventory Management

- Real-time stock tracking
- Batch/lot management
- Expiration date monitoring
- Stock level thresholds
- Automated reordering alerts

### Reporting & Analytics

- Sales analytics
- Inventory reports
- Supplier performance metrics
- Trend analysis

### Data Validation

- Input validation for all forms
- Data sanitization
- Error handling and reporting
- Comprehensive error logging

---

## Database Design

The system uses a relational database with the following key entities:

### Main Tables

- **Users**: User account information and authentication
- **Medicines**: Medicine inventory and details
- **Suppliers**: Supplier information and contact details
- **Stock Transactions**: Purchase and sale history
- **Alerts**: Alert logs and notifications
- **Categories**: Medicine categories and classifications

_Refer to `docs/architecture/er-diagram/` for complete ER diagram_

---

## Project Documentation

### Architecture Documentation

Located in `docs/architecture/`:

- **ER Diagram**: Complete database schema visualization
- **Use Case Diagram**: System functionality and user interactions
- **Flowchart**: Application workflow and process flows
- **Model Documentation**: Development methodology and approach

### Project Management

Located in `docs/project-management/`:

- **Proposal**: Project proposal and objectives
- **Gantt Chart**: Project timeline and milestones
- **Planning Documents**: Detailed project planning

---

## Key Features in Detail

### Real-Time Alerts

- Automated notifications for critical events
- Customizable alert thresholds
- Alert priority levels
- Alert dismissal and archiving

### Advanced Search

- Multi-criteria search
- Full-text search capabilities
- Saved search filters
- Export search results

### Responsive Design

- Mobile-friendly interface
- Adaptive layouts for all screen sizes
- Touch-friendly navigation
- Accessible UI components

### Data Security

- Encrypted password storage
- SQL injection prevention
- XSS protection
- CSRF token validation
- Session security

---

## File Organization

### Assets Organization

- **CSS Files**: Modular stylesheets with clear separation of concerns
- **Images**: SVG icons for consistent, scalable graphics
- **Variables**: Centralized CSS variables for theming

### Code Organization

- **Separation of Concerns**: Clear division between logic and presentation
- **Reusable Components**: Common functions in `includes/`
- **Modular Design**: Each feature in separate module files
- **Configuration Centralization**: All config in `config.php`

---

## Development Workflow

1. **Local Setup**: Install on XAMPP or local development server
2. **Development**: Make changes to PHP, CSS, or JavaScript files
3. **Testing**: Test functionality across modules
4. **Documentation**: Update relevant documentation in `docs/`
5. **Deployment**: Deploy to production server

---

## Future Enhancements

- Mobile application (iOS/Android)
- Advanced reporting and BI integration
- Multi-location support
- Barcode scanning
- API endpoints for third-party integration
- Email notifications
- SMS alerts
- Cloud synchronization
- Machine learning for demand forecasting

---

## Troubleshooting

### Common Issues

**Issue**: Database connection failed

- **Solution**: Check `config/config.php` credentials

**Issue**: Page styling not loading

- **Solution**: Clear browser cache, check CSS file paths

**Issue**: Session timeout too quick

- **Solution**: Adjust session timeout in PHP configuration

**Issue**: Search not working

- **Solution**: Verify form submission and database connectivity

---

## Support & Contact

For technical support, questions, or bug reports:

- Review project documentation in `docs/`
- Check the proposal document for project overview
- Refer to architecture diagrams for system design

---

## License

This project is proprietary software developed for pharmacy management purposes.

---

## Version Information

- **Current Version**: 1.0.0
- **Last Updated**: 2026-08-17
- **Status**: Active Development

---

## Acknowledgments

- Developed as a comprehensive pharmacy management solution
- Based on modern web development practices
- Designed with user experience and efficiency in mind
