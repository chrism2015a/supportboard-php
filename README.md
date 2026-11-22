# Support Board Replacement - PHP/Laravel Version

A comprehensive, production-quality replacement for [board.support](https://support.board) — built with **PHP/Laravel** and **MySQL**.

This is a full-featured SaaS support platform featuring smart chat, chatbots, ticketing, and knowledge base management.

## 🚀 Features

### Backend (PHP/Laravel)
- **Authentication**: JWT-based authentication with Tymon JWT Auth
- **Database**: MySQL with Eloquent ORM
- **API**: RESTful API with proper validation and error handling
- **Real-time**: WebSocket support (ready for Pusher/Socket.io integration)
- **CORS**: Cross-origin resource sharing configured for frontend
- **Seeder**: Database seeder with demo data

### API Endpoints
- **Authentication**: Login, Register, Logout, Get Current User
- **Dashboard**: Overview statistics and reports
- **Conversations**: Full CRUD for customer conversations
- **Messages**: Send and retrieve messages with typing indicators
- **Tickets**: Create, assign, and resolve support tickets
- **Articles**: Knowledge base with search and categories
- **Categories**: Hierarchical knowledge base categories
- **Departments**: Support departments with agent assignment
- **Chatbot**: AI chatbot with Q&A management and chat interface
- **Notifications**: Read/unread notifications
- **Settings**: Platform configuration
- **Users**: User management and agent roster

### Frontend (React)
- **Admin Dashboard**: Complete overview with stats cards, charts, and agent status
- **Conversations Management**: Search, filter, and manage all customer conversations
- **Ticket System**: Create, track, and resolve support tickets
- **Knowledge Base**: Browse, search, and manage help articles
- **Agent Management**: Team roster with online status and performance metrics
- **Chatbot Configuration**: Configure AI assistant, Q&A pairs, and conversation flows
- **Reports & Analytics**: Performance metrics, daily charts, source breakdown, agent rankings
- **Settings**: Platform configuration with tabs for general, appearance, notifications, chatbot, security, and integrations
- **Live Chat Widget**: Embeddable chat widget with AI-powered responses

## 🏗️ Architecture

```
supportboard-php/
├── app/
│   ├── Models/              # Eloquent models (17 models)
│   │   ├── User.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   ├── MessageReaction.php
│   │   ├── Ticket.php
│   │   ├── TicketMessage.php
│   │   ├── Category.php
│   │   ├── Article.php
│   │   ├── Department.php
│   │   ├── DepartmentUser.php
│   │   ├── ChatbotConfig.php
│   │   ├── ChatbotQnA.php
│   │   ├── ChatbotFlow.php
│   │   ├── ChatbotAnalytics.php
│   │   ├── Notification.php
│   │   ├── Setting.php
│   │   └── ApiToken.php
│   ├── Http/
│   │   ├── Controllers/     # 13 API controllers
│   │   └── Middleware/      # JWT authentication
│   └── Services/            # Business logic layer
├── bootstrap/               # Laravel bootstrap
├── config/                  # Configuration files
│   ├── app.php
│   ├── auth.php
│   ├── cors.php
│   ├── database.php         # MySQL config
│   ├── jwt.php              # JWT configuration
│   └── sanctum.php          # API token auth
├── database/
│   ├── migrations/          # 12 database migrations
│   └── seeders/             # Database seeder
├── public/                  # Public directory
│   └── index.php            # Entry point
├── resources/               # Assets and views
├── routes/
│   ├── api.php              # All API routes
│   ├── web.php              # Web routes
│   └── console.php          # Console commands
├── storage/                 # File storage
├── tests/                   # PHPUnit tests
├── composer.json
├── .env.example
└── README.md
```

## 📦 Tech Stack

**Backend:**
- PHP 8.1+
- Laravel 10.x
- MySQL 5.7+ / 8.0+
- JWT Authentication (Tymon JWT Auth)
- Laravel Sanctum for API tokens

**Database:**
- MySQL with InnoDB engine
- 12 migrations for complete schema
- Eloquent ORM with relationships

**Frontend:**
- React 18 + TypeScript
- Vite
- Tailwind CSS
- React Router DOM
- Zustand (state management)
- Axios (HTTP client)
- Lucide React (icons)

## 🚦 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js 16+ (for frontend)

### Quick Install (Recommended)

The project includes **four installation options** — pick whichever suits your workflow:

#### Option 1: Web Installer (Easiest)

Browse to `http://localhost/installer` after cloning. The wizard will:
1. Check system requirements (PHP, MySQL, Composer, Laravel)
2. Configure your database connection
3. Test the MySQL connection
4. Run migrations, seed demo data, and configure vLLM
5. Show a completion report with login credentials

#### Option 2: Bash Installer (Terminal)

```bash
# Interactive mode
bash install.sh

# Non-interactive (all defaults)
bash install.sh --force

# Skip database seeding
bash install.sh --skip-seed
```

#### Option 3: Artisan Command (Laravel CLI)

```bash
# Interactive CLI wizard
php artisan supportboard:install

# With all options
php artisan supportboard:install \
    --db-host=127.0.0.1 \
    --db-port=3306 \
    --db-name=supportboard \
    --db-user=root \
    --db-password=secret \
    --llm-endpoint=http://localhost:8000/v1 \
    --llm-model=gpt-3.5-turbo \
    --force
```

#### Option 4: Manual Setup (Full Control)

```bash
# Navigate to the project
cd supportboard-php

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Update .env with your MySQL credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=supportboard
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Generate application key
php artisan key:generate

# Create the database
mysql -u root -p -e "CREATE DATABASE supportboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed the database with demo data
php artisan db:seed

# Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000`

### Frontend Setup (from main project)

```bash
# Navigate to frontend directory
cd supportboard-replacement/frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The React admin dashboard will be available at `http://localhost:5173`

The Vite dev server proxies `/api` requests to `http://localhost:8000`

## 🔑 Demo Credentials

After running the database seeder, use these credentials:

- **Admin**: `admin@supportboard.local` / `admin123`
- **Agent**: `agent@supportboard.local` / `agent123`
- **User**: `user@example.com` / `user123`

## 📊 API Documentation

### Authentication

```bash
# Login
POST /api/auth/login
{
  "email": "admin@supportboard.local",
  "password": "admin123"
}

# Response
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "user": { ... }
}
```

### Conversations

```bash
# List conversations
GET /api/conversations?status=open&page=1

# Get conversation details
GET /api/conversations/1

# Create conversation
POST /api/conversations
{
  "user_id": 3,
  "department_id": 1,
  "source": "chat",
  "subject": "Help needed"
}

# Close conversation
POST /api/conversations/1/close
```

### Tickets

```bash
# List tickets
GET /api/tickets?status=open&priority=high

# Create ticket
POST /api/tickets
{
  "user_id": 3,
  "department_id": 1,
  "subject": "Login issue",
  "description": "Cannot login to my account",
  "priority": "high"
}

# Add message to ticket
POST /api/tickets/1/messages
{
  "content": "Have you tried clearing your cache?",
  "is_internal": false
}
```

### Chatbot

```bash
# Chat with bot
POST /api/chatbots/chat
{
  "message": "How do I reset my password?",
  "conversation_id": 1
}

# Response
{
  "reply": "To reset your password, go to the login page and click \"Forgot Password\"...",
  "transferred": false
}
```

## 🧪 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Production Setup

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Build frontend
npm run build

# Generate application key
php artisan key:generate --env=production

# Run migrations
php artisan migrate --force

# Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/supportboard-php/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /path/to/supportboard-php/public

    <Directory /path/to/supportboard-php/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

## 📁 Database Schema

### Core Tables
- `users` - User accounts with roles (admin, agent, user)
- `conversations` - Customer support conversations
- `messages` - Messages within conversations
- `message_reactions` - Emoji reactions on messages
- `tickets` - Support tickets with priority levels
- `ticket_messages` - Messages within tickets
- `departments` - Support departments
- `categories` - Knowledge base categories
- `articles` - Knowledge base articles

### Chatbot Tables
- `chatbot_configs` - Chatbot settings and configuration
- `chatbot_qnas` - Question and answer pairs
- `chatbot_flows` - Automated conversation flows
- `chatbot_analytics` - Performance metrics

### Other Tables
- `notifications` - User notifications
- `settings` - Platform settings (key-value pairs)
- `api_tokens` - API token management
- `report_cache` - Cached report data

## 🔒 Security

- JWT tokens with configurable TTL
- Password hashing with bcrypt
- CORS configuration for frontend
- Input validation on all API endpoints
- SQL injection protection via Eloquent ORM
- CSRF protection with Sanctum

## 🔄 Frontend Integration

The React frontend connects to the PHP backend through:

1. **Vite Proxy**: Development server proxies `/api` requests
2. **API Service**: Centralized axios configuration with interceptors
3. **Auth Store**: Zustand store for authentication state
4. **WebSocket Hook**: Real-time message handling

## 📝 License

MIT License - feel free to use this project for your own purposes.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
