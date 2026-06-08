# PharmaCore
PharmaCore is a web-based Medicine Inventory Management System designed to automate daily stock control, eliminate risks of selling expired medicines, and generate alert for low stock, expiring and expired medicines.

### project structure:
```text
pharmacore/
│
├── config/                  # Database connections and global configuration
│   └── db.php               # PDO or MySQLi connection script
│
├── assets/                  # Public frontend assets
│   ├── css/
│   │   └── style.css        # Main application styling
│   ├── js/
│   │   ├── main.js          # General JS (UI interactions)
│   │   └── alerts.js        # Logic for fetching low-stock/expiry notices via AJAX
│   └── images/              # UI icons or placeholder images
│
├── includes/                # Reusable HTML/PHP partials
│   ├── header.php           # Sidebar/Navbar navigation
│   ├── footer.php           # Scripts and closing tags
│   └── functions.php        # Global utility functions (e.g., date formatting, sanitization)
│
├── auth/                    # Authentication module
│   ├── login.php            # Login page UI
│   ├── logout.php           # Destroys session
│   ├── register.php         # Destroys session
│   └── auth_check.php       # Included at the top of pages to protect routes
│
├── modules/                 # Page-specific business logic and views
│   ├── dashboard.php        # Centralized dashboard panel (metrics overview)
│   ├── categories/          # Category Management
│   │   ├── index.php        # List categories
│   │   └── process.php      # Handle Add/Edit/Delete actions
│   ├── medicines/           # Medicine Management (CRUD)
│   │   ├── index.php        
│   │   └── process.php      
│   ├── stock/               # Stock Management
│   │   ├── index.php        # Inventory adjustment views
│   │   └── process.php      # Form processing for inventory changes
│   └── alerts/              # Dedicated views for system warnings
│       ├── low-stock.php    # Low stock thresholds page
│       └── expiry.php       # Expired / Nearing expiry views
│
├── docs/                    # Design assets & diagrams (More on this below)
│   ├── architecture/        # Use-case, Flowcharts, ER diagrams
│   └── project-management/  # Gantt charts, project documentation
│
├── .gitignore               # Tells Git which files to ignore (e.g., config/local_credentials)
├── index.php                # Entry point / Redirects to dashboard or login
└── README.md                # Project overview (based on your proposal Markdown)
```