# Game Backend Service

Modern game backend service with Laravel API, WebSocket, React frontend.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.3)
- **WebSocket**: Workerman
- **Frontend**: React 18 + Vite + TypeScript
- **Database**: MySQL 8.0
- **Cache**: Redis 7
- **Proxy**: Nginx

## Services

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 3000 | Unified entry point |
| Backend | 8080 | REST API |
| WebSocket | 3001 | Real-time communication |
| Frontend | 3000 (internal) | Dashboard |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache |

## Quick Start

```bash
# Local development
docker compose up --build

# Access dashboard
http://localhost:3000
```

## API Endpoints

- `GET /health` - Health check
- `GET /api/ping` - API ping
- `POST /api/login` - Player login
- `GET /api/wallet/balance` - Get balance
- `POST /api/wallet/credit` - Credit
- `POST /api/wallet/debit` - Debit
- `GET /api/slot/config` - Slot config
- `POST /api/slot/spin` - Spin

## Deployment

Deploy to Coolify with port 3000.
