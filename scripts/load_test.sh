#!/bin/bash

# Victoria Ferries API — Load Testing Script
# Supports both Apache Benchmark (ab) and wrk
# Usage: ./load_test.sh [BASE_URL] [CONCURRENCY] [REQUESTS]

set -euo pipefail

# ─── Configuration ──────────────────────────────────────────
BASE_URL="${1:-http://localhost:8000}"
CONCURRENCY="${2:-50}"
REQUESTS="${3:-500}"
RESULTS_DIR="$(dirname "$0")"
RESULTS_FILE="$RESULTS_DIR/load_test_results_$(date +%Y%m%d_%H%M%S).txt"
API_TOKEN=""

echo "╔══════════════════════════════════════════════════════╗"
echo "║   Victoria Ferries API — Load Testing Suite          ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
echo "Target:      $BASE_URL"
echo "Concurrency: $CONCURRENCY"
echo "Requests:    $REQUESTS"
echo "Results:     $RESULTS_FILE"
echo ""

# ─── Tool Detection ────────────────────────────────────────
TOOL=""
if command -v ab &> /dev/null; then
    TOOL="ab"
elif command -v wrk &> /dev/null; then
    TOOL="wrk"
else
    echo "ERROR: Neither 'ab' nor 'wrk' found. Install one:"
    echo "  sudo apt install apache2-utils   # for ab"
    echo "  sudo apt install wrk             # for wrk"
    exit 1
fi
echo "Using tool: $TOOL"
echo ""

# ─── Helper Functions ──────────────────────────────────────
run_test() {
    local method="$1"
    local endpoint="$2"
    local label="$3"
    local body="${4:-}"
    local auth="${5:-}"

    echo "──────────────────────────────────────────────────"
    echo "TEST: $label"
    echo "  $method $endpoint"
    echo "──────────────────────────────────────────────────"

    if [ "$TOOL" = "ab" ]; then
        local extra_args=""
        if [ -n "$auth" ]; then
            extra_args="-H 'Authorization: Bearer $auth'"
        fi
        if [ "$method" = "POST" ] && [ -n "$body" ]; then
            echo "$body" > /tmp/ab_post_data.json
            eval ab -n "$REQUESTS" -c "$CONCURRENCY" -p /tmp/ab_post_data.json -T "application/json" $extra_args "$BASE_URL$endpoint" 2>&1 | grep -E "Time taken for tests:|Failed requests:|Requests per second:|Transfer rate:|Percentage|50%|95%|99%"
        else
            if [ -n "$auth" ]; then
                ab -n "$REQUESTS" -c "$CONCURRENCY" -H "Authorization: Bearer $auth" "$BASE_URL$endpoint" 2>&1 | grep -E "Time taken for tests:|Failed requests:|Requests per second:|Transfer rate:|50%|95%|99%"
            else
                ab -n "$REQUESTS" -c "$CONCURRENCY" "$BASE_URL$endpoint" 2>&1 | grep -E "Time taken for tests:|Failed requests:|Requests per second:|Transfer rate:|50%|95%|99%"
            fi
        fi
    else
        # wrk
        local duration="10s"
        if [ -n "$auth" ]; then
            wrk -t4 -c"$CONCURRENCY" -d"$duration" -H "Authorization: Bearer $auth" "$BASE_URL$endpoint"
        else
            wrk -t4 -c"$CONCURRENCY" -d"$duration" "$BASE_URL$endpoint"
        fi
    fi

    echo ""
}

# ─── Authenticate (get API token) ──────────────────────────
echo "═══════════════════════════════════════════════════════"
echo " PHASE 1: Authentication"
echo "═══════════════════════════════════════════════════════"

LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/v1/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@ferrybooking.co.tz","password":"password"}' 2>/dev/null || echo '{}')

API_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | head -1 | cut -d'"' -f4 || echo "")

if [ -z "$API_TOKEN" ]; then
    echo "WARNING: Could not obtain API token. Authenticated tests will be skipped."
    echo "  Login response: $LOGIN_RESPONSE"
else
    echo "✓ Authenticated successfully. Token obtained."
fi
echo ""

# ─── Public Endpoint Tests ─────────────────────────────────
echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"
echo " PHASE 2: Public Endpoints (No Auth)" | tee -a "$RESULTS_FILE"
echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"

run_test "GET" "/api/portal/ferries" "Fetch Available Ferries" | tee -a "$RESULTS_FILE"

DATE=$(date -d "+7 days" +%Y-%m-%d 2>/dev/null || date -v+7d +%Y-%m-%d)
run_test "GET" "/api/portal/search/voyages?from=1&to=2&date=$DATE" "Search Voyages (Next Week)" | tee -a "$RESULTS_FILE"

run_test "GET" "/api/portal/routes" "Fetch Routes" | tee -a "$RESULTS_FILE"

# ─── Authenticated Endpoint Tests ──────────────────────────
if [ -n "$API_TOKEN" ]; then
    echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"
    echo " PHASE 3: Authenticated Endpoints" | tee -a "$RESULTS_FILE"
    echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"

    run_test "GET" "/api/v1/bookings" "List Bookings (Auth)" "" "$API_TOKEN" | tee -a "$RESULTS_FILE"

    # Reduced concurrency for write endpoints to avoid overwhelming the DB
    WRITE_CONCURRENCY=$((CONCURRENCY / 5))
    WRITE_REQUESTS=$((REQUESTS / 5))

    echo "NOTE: Write endpoint tests use reduced concurrency ($WRITE_CONCURRENCY) and requests ($WRITE_REQUESTS)" | tee -a "$RESULTS_FILE"

    if [ "$TOOL" = "ab" ]; then
        echo "──────────────────────────────────────────────────" | tee -a "$RESULTS_FILE"
        echo "TEST: Concurrent Booking Creation Stress Test" | tee -a "$RESULTS_FILE"
        echo "  POST /api/portal/bookings/fast" | tee -a "$RESULTS_FILE"
        echo "──────────────────────────────────────────────────" | tee -a "$RESULTS_FILE"

        BOOKING_BODY='{"voyage_id":1,"passengers":[{"first_name":"Load","last_name":"Test","gender":"male","nationality":"TZ","passenger_type_id":1}],"ferry_class_id":1}'
        echo "$BOOKING_BODY" > /tmp/ab_booking.json

        ab -n "$WRITE_REQUESTS" -c "$WRITE_CONCURRENCY" \
            -p /tmp/ab_booking.json -T "application/json" \
            -H "Authorization: Bearer $API_TOKEN" \
            "$BASE_URL/api/portal/bookings/fast" 2>&1 | grep -E "Time taken for tests:|Failed requests:|Requests per second:|Non-2xx" | tee -a "$RESULTS_FILE"
    fi
    echo "" | tee -a "$RESULTS_FILE"
fi

# ─── Summary ───────────────────────────────────────────────
echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"
echo " Load testing complete at $(date)" | tee -a "$RESULTS_FILE"
echo " Full results saved to: $RESULTS_FILE" | tee -a "$RESULTS_FILE"
echo "═══════════════════════════════════════════════════════" | tee -a "$RESULTS_FILE"
