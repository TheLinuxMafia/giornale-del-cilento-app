# Giornale del Cilento - AI-Powered News Platform

This project is a comprehensive news platform that integrates with WordPress and uses AI for content generation, designed specifically for the "Giornale del Cilento" newspaper.

## Project Structure

```
App/
├── backend/                 # Laravel 11 API Backend
│   ├── app/
│   │   ├── Http/Controllers/Api/  # API Controllers
│   │   ├── Models/               # Eloquent Models
│   │   ├── Services/             # Business Logic Services
│   │   └── Jobs/                 # Queue Jobs
│   ├── database/migrations/      # Database Migrations
│   ├── routes/api.php           # API Routes
│   └── config/                  # Configuration Files
├── frontend/                # Angular 17 SPA Frontend
│   ├── src/
│   │   ├── app/
│   │   │   ├── components/      # Angular Components
│   │   │   ├── services/        # Angular Services
│   │   │   ├── models/          # TypeScript Models
│   │   │   └── guards/          # Route Guards
│   │   └── assets/              # Static Assets
│   └── package.json
└── ANALISI_APPLICAZIONE.md  # Project Analysis Document
```

## Features

### MVP Features
- **WordPress Authentication**: Integration with WordPress user system via JWT
- **RSS Feed Management**: Create, update, delete, and manage RSS feeds
- **User Feed Preferences**: Journalists can select which feeds to view
- **AI Content Generation**: Generate articles from RSS titles/descriptions
- **Manual Article Creation**: Rich text editor with AI assistance for tags/SEO
- **Multi-AI Provider Support**: Support for OpenAI, Anthropic, Azure OpenAI
- **WordPress Publishing**: Direct publishing to WordPress with proper attribution
- **Concurrency Management**: Lock system for drafts and RSS item claims

### Technical Stack

#### Backend (Laravel 11)
- **Framework**: Laravel 11 with PHP 8.3
- **Database**: MySQL/PostgreSQL with Redis for caching/queues
- **Authentication**: Laravel Sanctum + WordPress JWT integration
- **RSS Processing**: Feed-IO library for RSS parsing
- **Queue System**: Laravel Horizon with Redis
- **Real-time**: Laravel Broadcasting with Pusher
- **AI Integration**: Multiple provider adapters (OpenAI, Anthropic, Azure)

#### Frontend (Angular 17)
- **Framework**: Angular 17 with TypeScript
- **UI Library**: Angular Material
- **State Management**: RxJS with Services
- **HTTP Client**: Angular HttpClient
- **Routing**: Angular Router with Guards
- **Internationalization**: Angular i18n (IT/EN)

## Getting Started

### Prerequisites
- PHP 8.3+
- Composer
- Node.js 18+
- MySQL/PostgreSQL
- Redis
- WordPress installation with JWT plugin

### Backend Setup

1. Navigate to the backend directory:
```bash
cd backend
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure database in `.env` file

6. Run migrations:
```bash
php artisan migrate
```

7. Start the development server:
```bash
php artisan serve
```

### Frontend Setup

1. Navigate to the frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
ng serve
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - WordPress authentication
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get current user

### Feeds Management
- `GET /api/feeds` - List all feeds
- `POST /api/feeds` - Create new feed (Editor/Admin only)
- `PUT /api/feeds/{id}` - Update feed (Editor/Admin only)
- `DELETE /api/feeds/{id}` - Delete feed (Editor/Admin only)

### User Feed Preferences
- `GET /api/me/feed-preferences` - Get user's feed preferences
- `PUT /api/me/feed-preferences` - Update user's feed preferences

### Articles/RSS Items
- `GET /api/articles` - List articles (filtered by user preferences)
- `POST /api/articles/{itemId}/claim` - Claim RSS item for editing
- `DELETE /api/articles/{itemId}/claim` - Release claim

### Drafts
- `GET /api/drafts` - List user's drafts
- `POST /api/drafts` - Create new draft
- `GET /api/drafts/{id}` - Get draft details
- `PATCH /api/drafts/{id}` - Update draft (with versioning)
- `POST /api/drafts/{id}/lock` - Acquire draft lock
- `DELETE /api/drafts/{id}/lock` - Release draft lock

### AI Integration
- `POST /api/ai/generate-from-rss` - Generate article from RSS item
- `POST /api/ai/seo-tags` - Generate SEO tags for content

### WordPress Integration
- `GET /api/wordpress/taxonomies` - Get WordPress categories/tags
- `POST /api/wordpress/publish` - Publish draft to WordPress

## Database Schema

### Core Tables
- `users` - Application users (linked to WordPress)
- `feeds` - RSS feed configurations
- `feed_items` - RSS feed items with processing status
- `user_feed_preferences` - User's feed visibility preferences
- `drafts` - Article drafts with versioning
- `editing_sessions` - Active editing sessions
- `providers` - AI provider configurations
- `models` - AI model configurations

## Development Workflow

1. **Feature Development**: Create feature branches from main
2. **Backend First**: Implement API endpoints and business logic
3. **Frontend Integration**: Connect Angular services to API
4. **Testing**: Write unit and integration tests
5. **Code Review**: Review code before merging
6. **Deployment**: Deploy to staging/production environments

## Security Considerations

- All API endpoints require authentication
- WordPress JWT tokens are validated on each request
- Rate limiting on AI API calls
- Input validation and sanitization
- CORS configuration for SPA
- Secure storage of AI provider credentials

## Performance Optimization

- Redis caching for frequently accessed data
- Queue system for AI generation and WordPress publishing
- Database indexing on frequently queried columns
- Image optimization before WordPress upload
- Lazy loading in Angular components

## Monitoring and Logging

- Structured logging with Laravel Log
- Queue monitoring with Horizon
- API response time tracking
- Error tracking and alerting
- User activity logging for audit trails

## Contributing

1. Follow PSR-12 coding standards for PHP
2. Use Angular style guide for TypeScript
3. Write comprehensive tests
4. Update documentation for new features
5. Follow semantic versioning

## License

This project is proprietary software for Giornale del Cilento.

