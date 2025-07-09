# iCapital Investor Management System

A web application that allows partner users to input investor information and upload documents for financial threshold verification.

## Features

- Investor information form with required fields
- File upload capability (up to 3MB)
- Relational database storage
- File system storage for uploaded documents
- Responsive web interface

## Prerequisites

### Software Requirements

1. **PHP 8.0 or higher**
   ```bash
   # macOS (using Homebrew)
   brew install php
   
   # Ubuntu/Debian
   sudo apt update
   sudo apt install php php-mysql php-mbstring php-xml php-curl
   ```

2. **MySQL 8.0 or higher**
   ```bash
   # macOS (using Homebrew)
   brew install mysql
   
   # Ubuntu/Debian
   sudo apt install mysql-server
   ```

3. **Node.js 16 or higher**
   ```bash
   # macOS (using Homebrew)
   brew install node
   
   # Ubuntu/Debian
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt-get install -y nodejs
   ```

4. **Apache or Nginx web server**
   ```bash
   # macOS (Apache comes pre-installed)
   sudo apachectl start
   
   # Ubuntu/Debian
   sudo apt install apache2
   ```

## Installation & Setup

### 1. Database Setup

1. **Start MySQL service**
   ```bash
   # macOS
   brew services start mysql
   
   # Ubuntu/Debian
   sudo systemctl start mysql
   sudo systemctl enable mysql
   ```

2. **Create database and user**
   ```bash
   mysql -u root -p
   ```
   
   In MySQL console:
   ```sql
   CREATE DATABASE icapital_db;
   CREATE USER 'icapital_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON icapital_db.* TO 'icapital_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

3. **Import database schema**
   ```bash
   mysql -u icapital_user -p icapital_db < backend/database/icapital-schema.sql
   ```

### 2. Backend Setup

1. **Configure database connection**
   ```bash
   cd backend
   cp config/icapital-db_credentials.example.php config/icapital-db_credentials.php
   ```
   
   Edit `config/icapital-db_credentials.php` with your database credentials:
   ```php
   <?php
   $user = "icapital_user";
   $pass = "your_secure_password";
   $db = "icapital_db";
   ?>
   ```

2. **Set up file upload directory**
   ```bash
   mkdir -p uploads/investors
   chmod 755 uploads
   chmod 755 uploads/investors
   ```

3. **Configure web server**
   
   For Apache, ensure mod_rewrite is enabled:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### 3. Frontend Setup

1. **Install dependencies**
   ```bash
   cd frontend
   npm install
   ```

2. **Configure API endpoint**
   
   Edit `src/config/api.js` and update the API base URL:
   ```javascript
   export const API_BASE_URL = 'http://localhost/icapital/backend';
   ```

3. **Build the application**
   ```bash
   npm run build
   ```

## Running the Application

### Development Mode

1. **Start the backend server**
   ```bash
   cd backend
   php -S localhost:8000
   ```

2. **Start the frontend development server**
   ```bash
   cd frontend
   npm start
   ```

3. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000

### Production Mode

1. **Build the frontend**
   ```bash
   cd frontend
   npm run build
   ```

2. **Deploy to web server**
   ```bash
   # Copy backend files to web server directory
   sudo cp -r backend /var/www/html/icapital/
   
   # Copy frontend build files
   sudo cp -r frontend/build/* /var/www/html/icapital/
   
   # Set proper permissions
   sudo chown -R www-data:www-data /var/www/html/icapital/
   sudo chmod -R 755 /var/www/html/icapital/
   ```

3. **Access the application**
   - Web application: http://your-domain/icapital/

## Project Structure

```
iCapital/
├── backend/
│   ├── api/
│   │   ├── icapital-investors.php
│   │   ├── icapital-upload.php
│   │   └── icapital-cors.php
│   ├── config/
│   │   ├── icapital-db_credentials.php
│   │   └── icapital-connect_db.php
│   ├── database/
│   │   └── icapital-schema.sql
│   └── uploads/
│       └── investors/
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── components/
│   │   ├── services/
│   │   └── config/
│   └── package.json
└── README.md
```

## API Endpoints

- `POST /api/icapital-investors.php` - Create new investor record
- `GET /api/icapital-investors.php` - Retrieve all investors
- `POST /api/icapital-upload.php` - Upload investor documents

## Security Considerations

- All input fields are validated and sanitized
- File uploads are restricted to specific file types and sizes
- Database queries use prepared statements to prevent SQL injection
- CORS is properly configured for cross-origin requests

## Troubleshooting

### Common Issues

1. **Database connection failed**
   - Verify MySQL service is running
   - Check database credentials in `config/db_credentials.php`
   - Ensure database and user exist

2. **File upload errors**
   - Check directory permissions on `uploads/` folder
   - Verify PHP upload settings in `php.ini`
   - Ensure file size doesn't exceed 3MB limit

3. **CORS errors**
   - Check CORS configuration in `api/icapital-cors.php`
   - Verify API endpoint URLs in frontend configuration

### Logs

- PHP error logs: `/var/log/apache2/error.log` (Ubuntu) or `/var/log/apache2/error_log` (macOS)
- MySQL logs: `/var/log/mysql/error.log`

## Support

For technical support or questions about the implementation, please refer to the code comments and API documentation within the source files.
