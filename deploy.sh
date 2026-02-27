#!/bin/bash

# Game Backend Deployment Script
# This script will:
# 1. Pull latest code from Git
# 2. Stop and remove containers
# 3. Remove volumes (reset database)
# 4. Rebuild and start containers

set -e

echo "=== Game Backend Deployment Script ==="

# Navigate to project directory
cd ~/woerkerman2

# Pull latest code
echo "1. Pulling latest code..."
git pull origin master

# Stop and remove containers (including volumes)
echo "2. Stopping containers and removing volumes..."
docker compose down -v

# Build and start containers
echo "3. Building and starting containers..."
docker compose up -d --build

# Wait for services to be ready
echo "4. Waiting for services..."
sleep 10

# Check status
echo "5. Checking container status..."
docker ps

echo "=== Deployment Complete ==="
echo "Frontend: http://localhost:3000"
echo "Backend API: http://localhost:8080"
echo "Health Check: curl http://localhost:8080/health"
