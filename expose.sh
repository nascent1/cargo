#!/bin/bash

echo "Starting Tunnels..."

# Start Localtunnel for Laravel API in the background
npx localtunnel --port 8000 --subdomain hcl-laravel-api > localtunnel.log 2>&1 &
LOCAL_PID=$!

echo "✅ Localtunnel (Laravel) started in background."
echo "   API URL: https://hcl-laravel-api.loca.lt/api"

# Wait a second for it to start
sleep 2

echo "Starting Ngrok for Vite..."
echo "✅ Ngrok (Vite) will run in the foreground. Press Ctrl+C to stop both tunnels."

# Start Ngrok for Vite in the foreground
ngrok http 5173

# When Ngrok is stopped (Ctrl+C), kill Localtunnel as well
kill $LOCAL_PID
echo "Tunnels stopped."
