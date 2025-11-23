#!/bin/bash

# Splash360 Tour - Quick Setup Script
# This script automates the setup process

set -e

echo "================================"
echo "Splash360 Tour - Quick Setup"
echo "================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed!${NC}"
    echo "Please install Docker first: https://docs.docker.com/get-docker/"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Error: Docker Compose is not installed!${NC}"
    echo "Please install Docker Compose first: https://docs.docker.com/compose/install/"
    exit 1
fi

echo -e "${GREEN}✓ Docker and Docker Compose are installed${NC}"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Create upload directories
echo -e "${YELLOW}Creating upload directories...${NC}"
mkdir -p public/uploads/properties
mkdir -p public/uploads/scenes
chmod -R 775 public/uploads
echo -e "${GREEN}✓ Upload directories created${NC}"
echo ""

# Start Docker containers
echo -e "${YELLOW}Starting Docker containers...${NC}"
docker-compose up -d

echo ""
echo -e "${GREEN}Waiting for services to be ready...${NC}"
sleep 10

echo ""
echo "================================"
echo -e "${GREEN}Setup Complete!${NC}"
echo "================================"
echo ""
echo "Access your application:"
echo -e "${GREEN}→ Main App:${NC} http://localhost:8080"
echo -e "${GREEN}→ phpMyAdmin:${NC} http://localhost:8081"
echo ""
echo "Default Platform Admin Credentials:"
echo -e "${YELLOW}Email:${NC} admin@splash360tour.com"
echo -e "${YELLOW}Password:${NC} admin123"
echo ""
echo -e "${RED}⚠ IMPORTANT: Change the admin password after first login!${NC}"
echo ""
echo "To stop the application:"
echo "  docker-compose down"
echo ""
echo "To view logs:"
echo "  docker-compose logs -f"
echo ""
echo "To restart:"
echo "  docker-compose restart"
echo ""
