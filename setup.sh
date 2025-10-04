#!/bin/bash

# Giornale del Cilento - Project Setup Script
# This script sets up both the Laravel backend and Angular frontend

set -e

echo "🚀 Setting up Giornale del Cilento project..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required tools are installed
check_requirements() {
    print_status "Checking requirements..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP 8.3+"
        exit 1
    fi
    
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed. Please install Composer"
        exit 1
    fi
    
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js 18+"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed. Please install npm"
        exit 1
    fi
    
    print_success "All requirements are met"
}

# Setup Laravel Backend
setup_backend() {
    print_status "Setting up Laravel backend..."
    
    cd backend
    
    # Install dependencies
    print_status "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader
    
    # Copy environment file
    if [ ! -f .env ]; then
        print_status "Creating .env file..."
        cp .env.example .env
        print_warning "Please configure your .env file with database and API credentials"
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate
    
    # Run migrations
    print_status "Running database migrations..."
    php artisan migrate --force
    
    # Create storage link
    print_status "Creating storage link..."
    php artisan storage:link
    
    # Clear and cache config
    print_status "Optimizing application..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    cd ..
    print_success "Backend setup completed"
}

# Setup Angular Frontend
setup_frontend() {
    print_status "Setting up Angular frontend..."
    
    cd frontend
    
    # Install dependencies
    print_status "Installing Node.js dependencies..."
    npm install
    
    # Build for production
    print_status "Building for production..."
    npm run build
    
    cd ..
    print_success "Frontend setup completed"
}

# Create systemd service files
create_services() {
    print_status "Creating systemd service files..."
    
    # Laravel service
    cat > giornale-backend.service << EOF
[Unit]
Description=Giornale del Cilento Backend
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$(pwd)/backend
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    # Nginx service
    cat > giornale-frontend.service << EOF
[Unit]
Description=Giornale del Cilento Frontend
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$(pwd)/frontend/dist/frontend
ExecStart=/usr/bin/python3 -m http.server 4200
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    print_success "Service files created"
    print_warning "To install services, run:"
    print_warning "sudo cp giornale-backend.service /etc/systemd/system/"
    print_warning "sudo cp giornale-frontend.service /etc/systemd/system/"
    print_warning "sudo systemctl daemon-reload"
    print_warning "sudo systemctl enable giornale-backend giornale-frontend"
    print_warning "sudo systemctl start giornale-backend giornale-frontend"
}

# Create Docker configuration
create_docker() {
    print_status "Creating Docker configuration..."
    
    # Docker Compose file
    cat > docker-compose.yml << EOF
version: '3.8'

services:
  app:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: giornale-backend
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./backend:/var/www
      - ./backend/storage:/var/www/storage
    ports:
      - "8000:8000"
    networks:
      - giornale-network
    depends_on:
      - db
      - redis

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: giornale-frontend
    restart: unless-stopped
    ports:
      - "4200:80"
    networks:
      - giornale-network

  db:
    image: mysql:8.0
    container_name: giornale-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: giornale_cilento
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_USER: giornale
      MYSQL_PASSWORD: giornalepassword
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - giornale-network

  redis:
    image: redis:7-alpine
    container_name: giornale-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - giornale-network

volumes:
  db_data:

networks:
  giornale-network:
    driver: bridge
EOF

    # Backend Dockerfile
    cat > backend/Dockerfile << EOF
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \\
    git \\
    curl \\
    libpng-dev \\
    libonig-dev \\
    libxml2-dev \\
    zip \\
    unzip \\
    nodejs \\
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Change current user to www
USER www-data

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 8000 and start php-fpm server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
EOF

    # Frontend Dockerfile
    cat > frontend/Dockerfile << EOF
FROM node:18-alpine as build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist/frontend /usr/share/nginx/html
COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
EOF

    # Nginx configuration
    cat > frontend/nginx.conf << EOF
events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    server {
        listen 80;
        server_name localhost;
        root /usr/share/nginx/html;
        index index.html;

        location / {
            try_files \$uri \$uri/ /index.html;
        }

        location /api {
            proxy_pass http://app:8000;
            proxy_set_header Host \$host;
            proxy_set_header X-Real-IP \$remote_addr;
            proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto \$scheme;
        }
    }
}
EOF

    print_success "Docker configuration created"
    print_warning "To run with Docker:"
    print_warning "docker-compose up -d"
}

# Main execution
main() {
    echo "🎯 Giornale del Cilento - AI-Powered News Platform"
    echo "=================================================="
    
    check_requirements
    
    # Ask user what to setup
    echo ""
    echo "What would you like to setup?"
    echo "1) Backend only"
    echo "2) Frontend only"
    echo "3) Both backend and frontend"
    echo "4) Docker configuration"
    echo "5) Systemd services"
    echo "6) Everything"
    echo ""
    read -p "Enter your choice (1-6): " choice
    
    case $choice in
        1)
            setup_backend
            ;;
        2)
            setup_frontend
            ;;
        3)
            setup_backend
            setup_frontend
            ;;
        4)
            create_docker
            ;;
        5)
            create_services
            ;;
        6)
            setup_backend
            setup_frontend
            create_docker
            create_services
            ;;
        *)
            print_error "Invalid choice"
            exit 1
            ;;
    esac
    
    echo ""
    print_success "Setup completed successfully!"
    echo ""
    echo "📋 Next steps:"
    echo "1. Configure your .env file in the backend directory"
    echo "2. Set up your WordPress installation with JWT plugin"
    echo "3. Configure AI provider credentials"
    echo "4. Start the development servers:"
    echo "   - Backend: cd backend && php artisan serve"
    echo "   - Frontend: cd frontend && ng serve"
    echo ""
    echo "🌐 Access URLs:"
    echo "   - Frontend: http://localhost:4200"
    echo "   - Backend API: http://localhost:8000/api"
    echo ""
}

# Run main function
main "$@"
