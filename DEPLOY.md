# Server Deployment Guide

## Architecture

```
Internet -> Your Nginx (80/443) -> Docker Containers
                                    ├── frontend:3000
                                    ├── backend:8080
                                    └── mysql:3306
```

## Quick Deploy

### 1. Clone and Build

```bash
# On your server
git clone https://github.com/billyz31/woerkerman2.git
cd woerkerman2
docker compose up -d --build
```

### 2. Configure Nginx Reverse Proxy

```bash
# Copy nginx config
sudo cp server/nginx.conf /etc/nginx/sites-available/game-backend
sudo ln -sf /etc/nginx/sites-available/game-backend /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
```

### 3. Setup Let's Encrypt (HTTPS)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate (replace your-domain.com)
sudo certbot --nginx -d your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

## Manual Server Commands

### Start containers
```bash
docker compose up -d
```

### View logs
```bash
docker compose logs -f
```

### Stop containers
```bash
docker compose down
```

### Rebuild
```bash
docker compose up -d --build --no-cache
```

## Environment Variables

Create `.env` file:

```env
MYSQL_ROOT_PASSWORD=your-secure-password
MYSQL_DATABASE=game
MYSQL_USER=game
MYSQL_PASSWORD=your-secure-password
DB_HOST=mysql
DB_DATABASE=game
DB_USERNAME=game
DB_PASSWORD=your-secure-password
```
