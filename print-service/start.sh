#!/bin/bash

# WARKOP Print Service Starter for macOS
# Run this script to start the auto-print service

echo "Starting WARKOP Print Service..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js not found. Please install Node.js first:"
    echo "   https://nodejs.org/en/download/"
    exit 1
fi

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Start the service
echo "🚀 Starting print service..."
node index.js
