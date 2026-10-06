<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Victoria Ferries API API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://127.0.0.1:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-bookings" class="tocify-header">
                <li class="tocify-item level-1" data-unique="bookings">
                    <a href="#bookings">Bookings</a>
                </li>
                                    <ul id="tocify-subheader-bookings" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="bookings-POSTapi-v1-bookings-hold">
                                <a href="#bookings-POSTapi-v1-bookings-hold">Hold Seats</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-POSTapi-v1-bookings-reserve">
                                <a href="#bookings-POSTapi-v1-bookings-reserve">Reserve Booking</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-GETapi-v1-bookings--reference-">
                                <a href="#bookings-GETapi-v1-bookings--reference-">Retrieve booking summary by reference number.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-GETapi-v1-bookings--reference--tickets">
                                <a href="#bookings-GETapi-v1-bookings--reference--tickets">Get tickets for a booking.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-POSTapi-v1-bookings--reference--cancel">
                                <a href="#bookings-POSTapi-v1-bookings--reference--cancel">Cancel Booking</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-POSTapi-v1-bookings--reference--pay">
                                <a href="#bookings-POSTapi-v1-bookings--reference--pay">Pay for Booking</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="bookings-GETapi-v1-bookings--reference--payment-status">
                                <a href="#bookings-GETapi-v1-bookings--reference--payment-status">Check Payment Status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-availability">
                                <a href="#endpoints-GETapi-v1-availability">Search available voyages and class capacities.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-seat-layout">
                                <a href="#endpoints-GETapi-v1-seat-layout">Get seat layout for a specific voyage and class.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-portal-login">
                                <a href="#endpoints-POSTapi-v1-portal-login">POST api/v1/portal/login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-splash">
                                <a href="#endpoints-GETapi-v1-portal-splash">Return public portal startup configurations and translations.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-voyages-search">
                                <a href="#endpoints-GETapi-v1-portal-voyages-search">Search for available voyages.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-payment-methods">
                                <a href="#endpoints-GETapi-v1-portal-payment-methods">GET api/v1/portal/payment-methods</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-dashboard-metrics">
                                <a href="#endpoints-GETapi-v1-portal-dashboard-metrics">Original metrics endpoint — preserved for backward compatibility.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-dashboard-command-center">
                                <a href="#endpoints-GETapi-v1-portal-dashboard-command-center">Operational Command Center — structured dashboard intelligence.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-portal-shift-overtime-request">
                                <a href="#endpoints-POSTapi-v1-portal-shift-overtime-request">POST api/v1/portal/shift/overtime-request</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-me">
                                <a href="#endpoints-GETapi-v1-portal-me">Get the authenticated user's profile, domain, roles, permissions, and wallet balance for the Vue 3 SPA.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-dashboard">
                                <a href="#endpoints-GETapi-v1-portal-dashboard">GET /api/v1/portal/dashboard</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-wallet">
                                <a href="#endpoints-GETapi-v1-portal-wallet">Get the agent's wallet summary.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-portal-wallet-top-up">
                                <a href="#endpoints-POSTapi-v1-portal-wallet-top-up">Request a wallet top-up.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-wallet-transactions">
                                <a href="#endpoints-GETapi-v1-portal-wallet-transactions">Get wallet transactions history.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-pending-refunds">
                                <a href="#endpoints-GETapi-v1-portal-pending-refunds">GET api/v1/portal/pending-refunds</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports">
                                <a href="#endpoints-GETapi-v1-portal-reports">Get the Sales & Commission Report data.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-filters">
                                <a href="#endpoints-GETapi-v1-portal-reports-filters">GET api/v1/portal/reports/filters</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-bookings">
                                <a href="#endpoints-GETapi-v1-portal-reports-bookings">GET api/v1/portal/reports/bookings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-cancellations">
                                <a href="#endpoints-GETapi-v1-portal-reports-cancellations">GET api/v1/portal/reports/cancellations</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-float-ledger">
                                <a href="#endpoints-GETapi-v1-portal-reports-float-ledger">GET api/v1/portal/reports/float-ledger</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-manifest">
                                <a href="#endpoints-GETapi-v1-portal-reports-manifest">GET api/v1/portal/reports/manifest</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-commission-reconciliation">
                                <a href="#endpoints-GETapi-v1-portal-reports-commission-reconciliation">GET api/v1/portal/reports/commission-reconciliation</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-peak-hours">
                                <a href="#endpoints-GETapi-v1-portal-reports-peak-hours">GET api/v1/portal/reports/peak-hours</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-reports-cancellation-trends">
                                <a href="#endpoints-GETapi-v1-portal-reports-cancellation-trends">GET api/v1/portal/reports/cancellation-trends</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-portal-coupons-validate">
                                <a href="#endpoints-POSTapi-v1-portal-coupons-validate">Validate a coupon code and preview discount.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-portal-announcements">
                                <a href="#endpoints-GETapi-v1-portal-announcements">Get active announcements for the portal.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mobile-splash">
                                <a href="#endpoints-GETapi-v1-mobile-splash">Provide bootstrap data for the mobile app.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mobile-search">
                                <a href="#endpoints-GETapi-v1-mobile-search">Search availability by departure and arrival port IDs.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mobile-seat-layout">
                                <a href="#endpoints-GETapi-v1-mobile-seat-layout">Get seat layout for a specific voyage and class.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-mobile-reserve">
                                <a href="#endpoints-POSTapi-v1-mobile-reserve">Create a reservation that needs to be paid at the office.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mobile-bookings--reference-">
                                <a href="#endpoints-GETapi-v1-mobile-bookings--reference-">Get booking details by reference.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-mobile-bookings--reference--cancel">
                                <a href="#endpoints-POSTapi-v1-mobile-bookings--reference--cancel">Cancel an active reservation.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-mobile-bookings--reference--pay">
                                <a href="#endpoints-POSTapi-v1-mobile-bookings--reference--pay">Initiate payment for a booking using Selcom.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mobile-bookings--reference--payment-status">
                                <a href="#endpoints-GETapi-v1-mobile-bookings--reference--payment-status">Poll payment status.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-boarding-scan">
                                <a href="#endpoints-POSTapi-v1-boarding-scan">Process a boarding scan (QR code).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-boarding-manifest--voyageId-">
                                <a href="#endpoints-GETapi-v1-boarding-manifest--voyageId-">Get boarding manifest for a voyage.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-boarding-stats--voyageId-">
                                <a href="#endpoints-GETapi-v1-boarding-stats--voyageId-">Get boarding statistics for a voyage.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-scanner-login">
                                <a href="#endpoints-POSTapi-v1-scanner-login">Authenticate port operator using PIN.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-scanner-logout">
                                <a href="#endpoints-POSTapi-v1-scanner-logout">Logout and revoke scanner token.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-scanner-voyages--voyageId--tickets">
                                <a href="#endpoints-GETapi-v1-scanner-voyages--voyageId--tickets">Fetch all tickets for a given voyage.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-scanner-verify">
                                <a href="#endpoints-POSTapi-v1-scanner-verify">Verify ticket validity without boarding.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-scanner-board">
                                <a href="#endpoints-POSTapi-v1-scanner-board">Board passenger (scan and mark boarded).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-symlink-fix">
                                <a href="#endpoints-GETapi-symlink-fix">GET api/symlink-fix</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-notifications">
                                <a href="#endpoints-GETapi-notifications">GET api/notifications</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-sales-portal" class="tocify-header">
                <li class="tocify-item level-1" data-unique="sales-portal">
                    <a href="#sales-portal">Sales Portal</a>
                </li>
                                    <ul id="tocify-subheader-sales-portal" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="sales-portal-GETapi-v1-portal-bookings">
                                <a href="#sales-portal-GETapi-v1-portal-bookings">List Bookings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-GETapi-v1-portal-bookings-availability-check">
                                <a href="#sales-portal-GETapi-v1-portal-bookings-availability-check">GET api/v1/portal/bookings/availability/check</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings-availability-lock">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings-availability-lock">POST api/v1/portal/bookings/availability/lock</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings-availability-unlock">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings-availability-unlock">POST api/v1/portal/bookings/availability/unlock</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-GETapi-v1-portal-bookings--id-">
                                <a href="#sales-portal-GETapi-v1-portal-bookings--id-">Show booking details.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--complete">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--complete">POST api/v1/portal/bookings/{id}/complete</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-PUTapi-v1-portal-bookings--id-">
                                <a href="#sales-portal-PUTapi-v1-portal-bookings--id-">PUT api/v1/portal/bookings/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--cancel">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--cancel">Cancel Booking</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">POST api/v1/portal/bookings/{id}/tickets/batch-reschedule</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">Cancel Single Ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">
                                <a href="#sales-portal-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">PUT api/v1/portal/bookings/{id}/tickets/{itemId}/passenger</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">Reschedule Ticket</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-GETapi-v1-portal-bookings--id--print">
                                <a href="#sales-portal-GETapi-v1-portal-bookings--id--print">GET api/v1/portal/bookings/{id}/print</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-GETapi-v1-portal-bookings--id--tickets--itemId--print">
                                <a href="#sales-portal-GETapi-v1-portal-bookings--id--tickets--itemId--print">GET api/v1/portal/bookings/{id}/tickets/{itemId}/print</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sales-portal-POSTapi-v1-portal-bookings--id--email">
                                <a href="#sales-portal-POSTapi-v1-portal-bookings--id--email">POST api/v1/portal/bookings/{id}/email</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-webhooks" class="tocify-header">
                <li class="tocify-item level-1" data-unique="webhooks">
                    <a href="#webhooks">Webhooks</a>
                </li>
                                    <ul id="tocify-subheader-webhooks" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="webhooks-POSTapi-v1-webhooks-selcom-callback">
                                <a href="#webhooks-POSTapi-v1-webhooks-selcom-callback">Selcom Payment Callback</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: August 24, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

        <h1 id="bookings">Bookings</h1>

    <p>Manage ferry bookings — create holds, reserve, confirm, cancel, and pay.
All endpoints require authentication via Bearer token.</p>

                                <h2 id="bookings-POSTapi-v1-bookings-hold">Hold Seats</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a 10-minute temporary seat hold. Seats are locked and released
automatically if not confirmed within the hold period.</p>

<span id="example-requests-POSTapi-v1-bookings-hold">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/bookings/hold" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 12,
    \"passengers\": [
        \"architecto\"
    ],
    \"currency\": \"TZS\",
    \"notes\": \"Window seat preferred\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/hold"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 12,
    "passengers": [
        "architecto"
    ],
    "currency": "TZS",
    "notes": "Window seat preferred"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-bookings-hold">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Booking held successfully.&quot;,
    &quot;data&quot;: {
        &quot;booking_reference&quot;: &quot;VF-20260824-ABC123&quot;,
        &quot;status&quot;: &quot;held&quot;,
        &quot;expires_at&quot;: &quot;2026-08-24T12:10:00Z&quot;,
        &quot;total_amount&quot;: 35000,
        &quot;currency&quot;: &quot;TZS&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Validation error&quot;,
    &quot;errors&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-bookings-hold" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-bookings-hold"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-bookings-hold"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-bookings-hold" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-bookings-hold">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-bookings-hold" data-method="POST"
      data-path="api/v1/bookings/hold"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-bookings-hold', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-bookings-hold"
                    onclick="tryItOut('POSTapi-v1-bookings-hold');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-bookings-hold"
                    onclick="cancelTryOut('POSTapi-v1-bookings-hold');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-bookings-hold"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/bookings/hold</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-bookings-hold"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-bookings-hold"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-bookings-hold"
               value="12"
               data-component="body">
    <br>
<p>The voyage to hold seats on. Example: <code>12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>passengers</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>List of passengers. Must contain at least 1.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>first_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.first_name"                data-endpoint="POSTapi-v1-bookings-hold"
               value="John"
               data-component="body">
    <br>
<p>Passenger first name. Example: <code>John</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>last_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.last_name"                data-endpoint="POSTapi-v1-bookings-hold"
               value="Doe"
               data-component="body">
    <br>
<p>Passenger last name. Example: <code>Doe</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.ferry_class_id"                data-endpoint="POSTapi-v1-bookings-hold"
               value="1"
               data-component="body">
    <br>
<p>Travel class ID. Example: <code>1</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>seat_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.seat_number"                data-endpoint="POSTapi-v1-bookings-hold"
               value="A12"
               data-component="body">
    <br>
<p>optional Preferred seat. Example: <code>A12</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>base_fare</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.base_fare"                data-endpoint="POSTapi-v1-bookings-hold"
               value="35000"
               data-component="body">
    <br>
<p>Ticket base fare. Example: <code>35000</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>discount_amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.discount_amount"                data-endpoint="POSTapi-v1-bookings-hold"
               value="5000"
               data-component="body">
    <br>
<p>optional Discount amount. Example: <code>5000</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-bookings-hold"
               value="TZS"
               data-component="body">
    <br>
<p>optional Currency code. Example: <code>TZS</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-bookings-hold"
               value="Window seat preferred"
               data-component="body">
    <br>
<p>optional Booking notes. Example: <code>Window seat preferred</code></p>
        </div>
        </form>

                    <h2 id="bookings-POSTapi-v1-bookings-reserve">Reserve Booking</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a reservation that needs to be paid at the office counter.
Supports one-way and round-trip bookings with multiple passengers.</p>

<span id="example-requests-POSTapi-v1-bookings-reserve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/bookings/reserve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"trip_type\": \"one_way\",
    \"voyage_id\": 12,
    \"ferry_class_id\": 1,
    \"return_voyage_id\": 15,
    \"return_ferry_class_id\": 1,
    \"passengers\": [
        \"architecto\"
    ],
    \"currency\": \"TZS\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/reserve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "trip_type": "one_way",
    "voyage_id": 12,
    "ferry_class_id": 1,
    "return_voyage_id": 15,
    "return_ferry_class_id": 1,
    "passengers": [
        "architecto"
    ],
    "currency": "TZS"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-bookings-reserve">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Booking reserved successfully.&quot;,
    &quot;data&quot;: {
        &quot;booking_reference&quot;: &quot;VF-20260824-XYZ789&quot;,
        &quot;status&quot;: &quot;reserved&quot;,
        &quot;total_amount&quot;: 70000
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Validation error&quot;,
    &quot;errors&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-bookings-reserve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-bookings-reserve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-bookings-reserve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-bookings-reserve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-bookings-reserve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-bookings-reserve" data-method="POST"
      data-path="api/v1/bookings/reserve"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-bookings-reserve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-bookings-reserve"
                    onclick="tryItOut('POSTapi-v1-bookings-reserve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-bookings-reserve"
                    onclick="cancelTryOut('POSTapi-v1-bookings-reserve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-bookings-reserve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/bookings/reserve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trip_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trip_type"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="one_way"
               data-component="body">
    <br>
<p>optional Trip type: one_way or round_trip. Example: <code>one_way</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="12"
               data-component="body">
    <br>
<p>The outbound voyage ID. Example: <code>12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="ferry_class_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="1"
               data-component="body">
    <br>
<p>The travel class ID. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="return_voyage_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="15"
               data-component="body">
    <br>
<p>optional Return voyage ID (required for round_trip). Example: <code>15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="return_ferry_class_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="1"
               data-component="body">
    <br>
<p>optional Return class ID (required for round_trip). Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>passengers</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>List of passengers.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.name"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="John Doe"
               data-component="body">
    <br>
<p>Full name. Example: <code>John Doe</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>passenger_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.passenger_type_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="1"
               data-component="body">
    <br>
<p>Passenger type. Example: <code>1</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.gender"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="male"
               data-component="body">
    <br>
<p>Gender: male, female, unknown. Example: <code>male</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>nationality_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.nationality_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>identity_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.identity_type_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>identity_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.identity_number"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>outbound_seat</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.outbound_seat"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="gzmiyvdljnikhway"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>gzmiyvdljnikhway</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>return_seat</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.return_seat"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="kcmyuwpwlvqwrsit"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>kcmyuwpwlvqwrsit</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.ferry_class_id"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="16"
               data-component="body">
    <br>
<p>backward compatibility. Must match an existing stored value. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>seat_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.seat_number"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="ngzmiyvdljnikhwa"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>ngzmiyvdljnikhwa</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-bookings-reserve"
               value="TZS"
               data-component="body">
    <br>
<p>optional Currency: TZS or USD. Example: <code>TZS</code></p>
        </div>
        </form>

                    <h2 id="bookings-GETapi-v1-bookings--reference-">Retrieve booking summary by reference number.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-bookings--reference-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/bookings/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-bookings--reference-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-bookings--reference-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-bookings--reference-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-bookings--reference-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-bookings--reference-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-bookings--reference-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-bookings--reference-" data-method="GET"
      data-path="api/v1/bookings/{reference}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-bookings--reference-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-bookings--reference-"
                    onclick="tryItOut('GETapi-v1-bookings--reference-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-bookings--reference-"
                    onclick="cancelTryOut('GETapi-v1-bookings--reference-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-bookings--reference-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/bookings/{reference}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-bookings--reference-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-bookings--reference-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reference"                data-endpoint="GETapi-v1-bookings--reference-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="bookings-GETapi-v1-bookings--reference--tickets">Get tickets for a booking.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-bookings--reference--tickets">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/bookings/1/tickets" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/1/tickets"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-bookings--reference--tickets">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-bookings--reference--tickets" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-bookings--reference--tickets"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-bookings--reference--tickets"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-bookings--reference--tickets" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-bookings--reference--tickets">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-bookings--reference--tickets" data-method="GET"
      data-path="api/v1/bookings/{reference}/tickets"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-bookings--reference--tickets', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-bookings--reference--tickets"
                    onclick="tryItOut('GETapi-v1-bookings--reference--tickets');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-bookings--reference--tickets"
                    onclick="cancelTryOut('GETapi-v1-bookings--reference--tickets');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-bookings--reference--tickets"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/bookings/{reference}/tickets</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-bookings--reference--tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-bookings--reference--tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reference"                data-endpoint="GETapi-v1-bookings--reference--tickets"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="bookings-POSTapi-v1-bookings--reference--cancel">Cancel Booking</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Cancel an active booking. Applies cancellation penalty if configured.
For agent bookings, refund is held as a pending refund until admin release.
For direct bookings, refund is processed via the original payment gateway.</p>

<span id="example-requests-POSTapi-v1-bookings--reference--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"Customer requested cancellation\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "Customer requested cancellation"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-bookings--reference--cancel">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Booking cancelled successfully.&quot;,
    &quot;data&quot;: {
        &quot;booking_reference&quot;: &quot;VF-20260824-ABC123&quot;,
        &quot;status&quot;: &quot;cancelled&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking is already cancelled or expired.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-bookings--reference--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-bookings--reference--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-bookings--reference--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-bookings--reference--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-bookings--reference--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-bookings--reference--cancel" data-method="POST"
      data-path="api/v1/bookings/{reference}/cancel"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-bookings--reference--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-bookings--reference--cancel"
                    onclick="tryItOut('POSTapi-v1-bookings--reference--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-bookings--reference--cancel"
                    onclick="cancelTryOut('POSTapi-v1-bookings--reference--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-bookings--reference--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/bookings/{reference}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-bookings--reference--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-bookings--reference--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reference"                data-endpoint="POSTapi-v1-bookings--reference--cancel"
               value="VF-20260824-ABC123"
               data-component="url">
    <br>
<p>The booking reference. Example: <code>VF-20260824-ABC123</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-bookings--reference--cancel"
               value="Customer requested cancellation"
               data-component="body">
    <br>
<p>optional Cancellation reason. Example: <code>Customer requested cancellation</code></p>
        </div>
        </form>

                    <h2 id="bookings-POSTapi-v1-bookings--reference--pay">Pay for Booking</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Initiate mobile money payment via Selcom gateway. A USSD push notification
is sent to the provided phone number. Poll the payment-status endpoint to check completion.</p>

<span id="example-requests-POSTapi-v1-bookings--reference--pay">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/pay" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"phone_number\": \"255712345678\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/pay"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "phone_number": "255712345678"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-bookings--reference--pay">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;A payment prompt has been sent to your phone. Please confirm.&quot;,
    &quot;data&quot;: {
        &quot;poll_url&quot;: &quot;http://localhost:8000/api/v1/bookings/VF-20260824-ABC123/payment-status&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking is already paid.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-bookings--reference--pay" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-bookings--reference--pay"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-bookings--reference--pay"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-bookings--reference--pay" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-bookings--reference--pay">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-bookings--reference--pay" data-method="POST"
      data-path="api/v1/bookings/{reference}/pay"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-bookings--reference--pay', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-bookings--reference--pay"
                    onclick="tryItOut('POSTapi-v1-bookings--reference--pay');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-bookings--reference--pay"
                    onclick="cancelTryOut('POSTapi-v1-bookings--reference--pay');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-bookings--reference--pay"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/bookings/{reference}/pay</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-bookings--reference--pay"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-bookings--reference--pay"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reference"                data-endpoint="POSTapi-v1-bookings--reference--pay"
               value="VF-20260824-ABC123"
               data-component="url">
    <br>
<p>The booking reference. Example: <code>VF-20260824-ABC123</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone_number"                data-endpoint="POSTapi-v1-bookings--reference--pay"
               value="255712345678"
               data-component="body">
    <br>
<p>Mobile money number. Example: <code>255712345678</code></p>
        </div>
        </form>

                    <h2 id="bookings-GETapi-v1-bookings--reference--payment-status">Check Payment Status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Poll this endpoint after initiating payment to check if the mobile money
transaction has been completed.</p>

<span id="example-requests-GETapi-v1-bookings--reference--payment-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/payment-status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/bookings/VF-20260824-ABC123/payment-status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-bookings--reference--payment-status">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;payment_status&quot;: &quot;paid&quot;,
        &quot;booking_status&quot;: &quot;confirmed&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;payment_status&quot;: &quot;pending&quot;,
        &quot;booking_status&quot;: &quot;held&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-bookings--reference--payment-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-bookings--reference--payment-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-bookings--reference--payment-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-bookings--reference--payment-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-bookings--reference--payment-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-bookings--reference--payment-status" data-method="GET"
      data-path="api/v1/bookings/{reference}/payment-status"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-bookings--reference--payment-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-bookings--reference--payment-status"
                    onclick="tryItOut('GETapi-v1-bookings--reference--payment-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-bookings--reference--payment-status"
                    onclick="cancelTryOut('GETapi-v1-bookings--reference--payment-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-bookings--reference--payment-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/bookings/{reference}/payment-status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-bookings--reference--payment-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-bookings--reference--payment-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reference"                data-endpoint="GETapi-v1-bookings--reference--payment-status"
               value="VF-20260824-ABC123"
               data-component="url">
    <br>
<p>The booking reference. Example: <code>VF-20260824-ABC123</code></p>
            </div>
                    </form>

                <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-v1-availability">Search available voyages and class capacities.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-availability">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/availability" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"route_id\": 16,
    \"departure_port\": 16,
    \"arrival_port\": 16,
    \"date\": \"2052-09-16\",
    \"return_date\": \"2052-09-16\",
    \"return_route_id\": 16,
    \"passengers_count\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/availability"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "route_id": 16,
    "departure_port": 16,
    "arrival_port": 16,
    "date": "2052-09-16",
    "return_date": "2052-09-16",
    "return_route_id": 16,
    "passengers_count": 22
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-availability">
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Validation error&quot;,
    &quot;errors&quot;: {
        &quot;route_id&quot;: [
            &quot;The selected route id is invalid.&quot;
        ],
        &quot;departure_port&quot;: [
            &quot;The selected departure port is invalid.&quot;
        ],
        &quot;arrival_port&quot;: [
            &quot;The selected arrival port is invalid.&quot;
        ],
        &quot;return_route_id&quot;: [
            &quot;The selected return route id is invalid.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-availability" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-availability"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-availability"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-availability" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-availability">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-availability" data-method="GET"
      data-path="api/v1/availability"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-availability', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-availability"
                    onclick="tryItOut('GETapi-v1-availability');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-availability"
                    onclick="cancelTryOut('GETapi-v1-availability');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-availability"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/availability</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>route_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="route_id"                data-endpoint="GETapi-v1-availability"
               value="16"
               data-component="body">
    <br>
<p>This field is required when none of <code>departure_port</code> and <code>arrival_port</code> are present. Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>departure_port</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="departure_port"                data-endpoint="GETapi-v1-availability"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>route_id</code> is not present. Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>arrival_port</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="arrival_port"                data-endpoint="GETapi-v1-availability"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>departure_port</code> is present. Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date"                data-endpoint="GETapi-v1-availability"
               value="2052-09-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>today</code>. Example: <code>2052-09-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="return_date"                data-endpoint="GETapi-v1-availability"
               value="2052-09-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>date</code>. Example: <code>2052-09-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_route_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="return_route_id"                data-endpoint="GETapi-v1-availability"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>passengers_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers_count"                data-endpoint="GETapi-v1-availability"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 50. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-seat-layout">Get seat layout for a specific voyage and class.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-seat-layout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/seat-layout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 16,
    \"class_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/seat-layout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 16,
    "class_id": 16
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-seat-layout">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;layout&quot;: {
        &quot;version&quot;: 1,
        &quot;rows&quot;: 26,
        &quot;columns&quot;: 5,
        &quot;grid&quot;: [
            [
                &quot;A1&quot;,
                &quot;A2&quot;,
                null,
                &quot;A3&quot;,
                &quot;A4&quot;
            ],
            [
                &quot;B1&quot;,
                &quot;B2&quot;,
                null,
                &quot;B3&quot;,
                &quot;B4&quot;
            ],
            [
                &quot;C1&quot;,
                &quot;C2&quot;,
                null,
                &quot;C3&quot;,
                &quot;C4&quot;
            ],
            [
                &quot;D1&quot;,
                &quot;D2&quot;,
                null,
                &quot;D3&quot;,
                &quot;D4&quot;
            ],
            [
                &quot;E1&quot;,
                &quot;E2&quot;,
                null,
                &quot;E3&quot;,
                &quot;E4&quot;
            ],
            [
                &quot;F1&quot;,
                &quot;F2&quot;,
                null,
                &quot;F3&quot;,
                &quot;F4&quot;
            ],
            [
                &quot;G1&quot;,
                &quot;G2&quot;,
                null,
                &quot;G3&quot;,
                &quot;G4&quot;
            ],
            [
                &quot;H1&quot;,
                &quot;H2&quot;,
                null,
                &quot;H3&quot;,
                &quot;H4&quot;
            ],
            [
                &quot;I1&quot;,
                &quot;I2&quot;,
                null,
                &quot;I3&quot;,
                &quot;I4&quot;
            ],
            [
                &quot;J1&quot;,
                &quot;J2&quot;,
                null,
                &quot;J3&quot;,
                &quot;J4&quot;
            ],
            [
                &quot;K1&quot;,
                &quot;K2&quot;,
                null,
                &quot;K3&quot;,
                &quot;K4&quot;
            ],
            [
                &quot;L1&quot;,
                &quot;L2&quot;,
                null,
                &quot;L3&quot;,
                &quot;L4&quot;
            ],
            [
                &quot;M1&quot;,
                &quot;M2&quot;,
                null,
                &quot;M3&quot;,
                &quot;M4&quot;
            ],
            [
                &quot;N1&quot;,
                &quot;N2&quot;,
                null,
                &quot;N3&quot;,
                &quot;N4&quot;
            ],
            [
                &quot;O1&quot;,
                &quot;O2&quot;,
                null,
                &quot;O3&quot;,
                &quot;O4&quot;
            ],
            [
                &quot;P1&quot;,
                &quot;P2&quot;,
                null,
                &quot;P3&quot;,
                &quot;P4&quot;
            ],
            [
                &quot;Q1&quot;,
                &quot;Q2&quot;,
                null,
                &quot;Q3&quot;,
                &quot;Q4&quot;
            ],
            [
                &quot;R1&quot;,
                &quot;R2&quot;,
                null,
                &quot;R3&quot;,
                &quot;R4&quot;
            ],
            [
                &quot;S1&quot;,
                &quot;S2&quot;,
                null,
                &quot;S3&quot;,
                &quot;S4&quot;
            ],
            [
                &quot;T1&quot;,
                &quot;T2&quot;,
                null,
                &quot;T3&quot;,
                &quot;T4&quot;
            ],
            [
                &quot;U1&quot;,
                &quot;U2&quot;,
                null,
                &quot;U3&quot;,
                &quot;U4&quot;
            ],
            [
                &quot;V1&quot;,
                &quot;V2&quot;,
                null,
                &quot;V3&quot;,
                &quot;V4&quot;
            ],
            [
                &quot;W1&quot;,
                &quot;W2&quot;,
                null,
                &quot;W3&quot;,
                &quot;W4&quot;
            ],
            [
                &quot;X1&quot;,
                &quot;X2&quot;,
                null,
                &quot;X3&quot;,
                &quot;X4&quot;
            ],
            [
                &quot;Y1&quot;,
                &quot;Y2&quot;,
                null,
                &quot;Y3&quot;,
                &quot;Y4&quot;
            ],
            [
                &quot;Z1&quot;,
                &quot;Z2&quot;,
                null,
                &quot;Z3&quot;,
                &quot;Z4&quot;
            ]
        ],
        &quot;sold&quot;: [
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;
        ],
        &quot;held&quot;: [],
        &quot;blocked&quot;: []
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-seat-layout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-seat-layout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-seat-layout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-seat-layout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-seat-layout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-seat-layout" data-method="GET"
      data-path="api/v1/seat-layout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-seat-layout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-seat-layout"
                    onclick="tryItOut('GETapi-v1-seat-layout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-seat-layout"
                    onclick="cancelTryOut('GETapi-v1-seat-layout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-seat-layout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/seat-layout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-seat-layout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-seat-layout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="GETapi-v1-seat-layout"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="class_id"                data-endpoint="GETapi-v1-seat-layout"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-portal-login">POST api/v1/portal/login</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"phone\": \"architecto\",
    \"password\": \"|]|{+-\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "phone": "architecto",
    "password": "|]|{+-"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-login">
</span>
<span id="execution-results-POSTapi-v1-portal-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-login" data-method="POST"
      data-path="api/v1/portal/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-login"
                    onclick="tryItOut('POSTapi-v1-portal-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-login"
                    onclick="cancelTryOut('POSTapi-v1-portal-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-portal-login"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-portal-login"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-portal-splash">Return public portal startup configurations and translations.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-splash">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/splash" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/splash"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-splash">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;platform_name&quot;: &quot;Victoria Ferries Booking Platform&quot;,
    &quot;supported_locales&quot;: [
        &quot;en&quot;,
        &quot;sw&quot;,
        &quot;fr&quot;,
        &quot;ar&quot;
    ],
    &quot;languages&quot;: [
        {
            &quot;code&quot;: &quot;en&quot;,
            &quot;name&quot;: &quot;English&quot;
        },
        {
            &quot;code&quot;: &quot;sw&quot;,
            &quot;name&quot;: &quot;Swahili&quot;
        },
        {
            &quot;code&quot;: &quot;fr&quot;,
            &quot;name&quot;: &quot;French&quot;
        },
        {
            &quot;code&quot;: &quot;ar&quot;,
            &quot;name&quot;: &quot;Arabic&quot;
        }
    ],
    &quot;translations&quot;: {
        &quot;auth&quot;: {
            &quot;sign_in&quot;: &quot;Sign In&quot;,
            &quot;sign_out&quot;: &quot;Sign Out&quot;,
            &quot;join_free&quot;: &quot;Join Free&quot;,
            &quot;my_dashboard&quot;: &quot;My Dashboard&quot;,
            &quot;signed_in_as&quot;: &quot;Signed in as&quot;,
            &quot;beekeeper&quot;: &quot;Beekeeper&quot;,
            &quot;consumer&quot;: &quot;Consumer&quot;
        },
        &quot;nav&quot;: {
            &quot;shop&quot;: &quot;Shop&quot;,
            &quot;shop_honey&quot;: &quot;Shop Honey&quot;,
            &quot;events&quot;: &quot;Events&quot;,
            &quot;membership&quot;: &quot;Membership&quot;,
            &quot;training&quot;: &quot;Training&quot;,
            &quot;dashboard&quot;: &quot;Dashboard&quot;,
            &quot;my_profile&quot;: &quot;My Profile&quot;,
            &quot;my_hives&quot;: &quot;My Hives&quot;,
            &quot;my_batches&quot;: &quot;My Batches&quot;,
            &quot;my_products&quot;: &quot;My Products&quot;,
            &quot;my_orders&quot;: &quot;My Orders&quot;,
            &quot;my_events&quot;: &quot;My Events&quot;,
            &quot;my_trainings&quot;: &quot;My Trainings&quot;,
            &quot;documents&quot;: &quot;Documents&quot;,
            &quot;star_ratings&quot;: &quot;Star Ratings&quot;,
            &quot;commerce_learning&quot;: &quot;Commerce &amp; Learning&quot;
        },
        &quot;common&quot;: {
            &quot;cancel&quot;: &quot;Cancel&quot;,
            &quot;confirm&quot;: &quot;Confirm&quot;,
            &quot;notice&quot;: &quot;Notice&quot;,
            &quot;ok&quot;: &quot;OK&quot;,
            &quot;other&quot;: &quot;Other&quot;,
            &quot;saving&quot;: &quot;Saving...&quot;,
            &quot;submitting&quot;: &quot;Submitting...&quot;,
            &quot;uploading&quot;: &quot;Uploading...&quot;
        },
        &quot;landing&quot;: {
            &quot;title_part1&quot;: &quot;Tanzania&#039;s Premium&quot;,
            &quot;title_part2&quot;: &quot;Honey Platform&quot;,
            &quot;subtitle&quot;: &quot;🍯 From Hive to Home&quot;,
            &quot;description&quot;: &quot;Connecting beekeepers to global markets through quality certification, traceability, and fair trade. Every jar tells a story of nature, community, and trust.&quot;,
            &quot;shop_btn&quot;: &quot;Shop Now&quot;,
            &quot;join_btn&quot;: &quot;Join as Beekeeper&quot;,
            &quot;are_you_beekeeper&quot;: &quot;Are you a beekeeper?&quot;,
            &quot;apply_to_join&quot;: &quot;Apply to join&quot;,
            &quot;apply_now&quot;: &quot;Apply Now&quot;,
            &quot;verified_beekeepers&quot;: &quot;Verified Beekeepers&quot;,
            &quot;premium_honey&quot;: &quot;Premium Honey&quot;,
            &quot;blockchain&quot;: &quot;Blockchain Verified&quot;,
            &quot;active_cooperatives&quot;: &quot;Active Cooperatives&quot;,
            &quot;pillars_title&quot;: &quot;Our Four Pillars&quot;,
            &quot;pillars_subtitle&quot;: &quot;Building a sustainable honey ecosystem through innovation and inclusion&quot;,
            &quot;traceability_title&quot;: &quot;Full Traceability&quot;,
            &quot;traceability_desc&quot;: &quot;Track every jar from the hive to your home with blockchain-verified provenance.&quot;,
            &quot;market_title&quot;: &quot;Fair Market Access&quot;,
            &quot;market_desc&quot;: &quot;Direct market connections ensuring fair prices for every beekeeper.&quot;,
            &quot;inclusion_title&quot;: &quot;Financial Inclusion&quot;,
            &quot;inclusion_desc&quot;: &quot;Mobile payments, cooperative banking, and transparent settlement systems.&quot;,
            &quot;journey_title&quot;: &quot;The Honey Journey&quot;,
            &quot;journey_subtitle&quot;: &quot;From hive to your table &mdash; verified at every step&quot;,
            &quot;step1_title&quot;: &quot;Harvest&quot;,
            &quot;step1_desc&quot;: &quot;Beekeepers harvest from their managed apiaries using sustainable practices.&quot;,
            &quot;step2_title&quot;: &quot;Quality Testing&quot;,
            &quot;step2_desc&quot;: &quot;Every batch undergoes sommelier inspection and accredited lab testing.&quot;,
            &quot;step3_title&quot;: &quot;Certified &amp; Packaged&quot;,
            &quot;step3_desc&quot;: &quot;Approved honey is branded, barcoded (EAN-13), and packaged for retail.&quot;,
            &quot;step4_title&quot;: &quot;Delivered to You&quot;,
            &quot;step4_desc&quot;: &quot;Order online and have verified, premium honey delivered to your door.&quot;,
            &quot;cta_title&quot;: &quot;Ready to taste Tanzania&#039;s finest?&quot;,
            &quot;cta_subtitle&quot;: &quot;Join thousands of satisfied customers and support local beekeepers.&quot;
        },
        &quot;cart&quot;: {
            &quot;delivery&quot;: &quot;Delivery&quot;,
            &quot;title&quot;: &quot;Shopping Cart&quot;,
            &quot;empty_title&quot;: &quot;Your cart is empty&quot;,
            &quot;empty_desc&quot;: &quot;Browse our shop and add products you love.&quot;,
            &quot;browse_shop&quot;: &quot;Browse Shop&quot;,
            &quot;continue_shopping&quot;: &quot;Continue Shopping&quot;,
            &quot;remove&quot;: &quot;Remove&quot;,
            &quot;kg&quot;: &quot;kg&quot;,
            &quot;subtotal&quot;: &quot;Subtotal&quot;,
            &quot;calculated_at_checkout&quot;: &quot;Calculated at checkout&quot;,
            &quot;discount&quot;: &quot;Discount&quot;,
            &quot;coupon_discount&quot;: &quot;Coupon Discount&quot;,
            &quot;total&quot;: &quot;Total&quot;,
            &quot;have_coupon&quot;: &quot;Have a coupon?&quot;,
            &quot;enter_code&quot;: &quot;Enter code&quot;,
            &quot;apply&quot;: &quot;Apply&quot;,
            &quot;membership_discount_applied&quot;: &quot;Membership discount applied&quot;,
            &quot;your_details&quot;: &quot;Your Details&quot;,
            &quot;sign_in&quot;: &quot;Sign in for faster checkout&quot;,
            &quot;no_account_needed&quot;: &quot;No account needed &mdash; order as guest&quot;,
            &quot;full_name&quot;: &quot;Full Name&quot;,
            &quot;email&quot;: &quot;Email&quot;,
            &quot;phone&quot;: &quot;Phone&quot;,
            &quot;delivery_address&quot;: &quot;Delivery Address&quot;,
            &quot;city&quot;: &quot;City&quot;,
            &quot;processing&quot;: &quot;Processing...&quot;,
            &quot;order_placed_title&quot;: &quot;Order Placed Successfully!&quot;,
            &quot;order_number&quot;: &quot;Order Number&quot;,
            &quot;already_ordered&quot;: &quot;Already ordered? Track your order.&quot;,
            &quot;see_history&quot;: &quot;See Order History&quot;
        },
        &quot;shop&quot;: {
            &quot;title_part1&quot;: &quot;Our Honey&quot;,
            &quot;title_part2&quot;: &quot;Collection&quot;,
            &quot;description&quot;: &quot;Discover premium, certified Tanzanian honey &mdash; from wildflower to monofloral, every jar is lab-tested and traceable.&quot;,
            &quot;search_placeholder&quot;: &quot;Search products...&quot;,
            &quot;categories_title&quot;: &quot;Categories&quot;,
            &quot;all_products&quot;: &quot;All Products&quot;,
            &quot;price_range&quot;: &quot;Price Range&quot;,
            &quot;min&quot;: &quot;Min&quot;,
            &quot;max&quot;: &quot;Max&quot;,
            &quot;min_rating&quot;: &quot;Minimum Rating&quot;,
            &quot;all_ratings&quot;: &quot;All Ratings&quot;,
            &quot;and_up&quot;: &quot;&amp; Up&quot;,
            &quot;featured&quot;: &quot;Featured&quot;,
            &quot;apply&quot;: &quot;Apply&quot;,
            &quot;clear_filters&quot;: &quot;Clear Filters&quot;,
            &quot;clear_all_filters&quot;: &quot;Clear All Filters&quot;,
            &quot;adjust_filters&quot;: &quot;Try adjusting your filters&quot;,
            &quot;no_products&quot;: &quot;No products found&quot;,
            &quot;sort_latest&quot;: &quot;Latest&quot;,
            &quot;sort_price_low&quot;: &quot;Price: Low to High&quot;,
            &quot;sort_price_high&quot;: &quot;Price: High to Low&quot;,
            &quot;sort_rating&quot;: &quot;Top Rated&quot;,
            &quot;sort_popular&quot;: &quot;Most Popular&quot;,
            &quot;product&quot;: &quot;product&quot;,
            &quot;reviews&quot;: &quot;reviews&quot;,
            &quot;sale&quot;: &quot;Sale&quot;,
            &quot;available&quot;: &quot;available&quot;,
            &quot;certified_badge&quot;: &quot;TBS Certified&quot;
        },
        &quot;product_detail&quot;: {
            &quot;shop&quot;: &quot;Shop&quot;,
            &quot;sale&quot;: &quot;Sale&quot;,
            &quot;certified_badge&quot;: &quot;TBS Certified&quot;,
            &quot;verified&quot;: &quot;Verified&quot;,
            &quot;available_stock&quot;: &quot;Available Stock&quot;,
            &quot;total&quot;: &quot;Total&quot;,
            &quot;add_to_cart&quot;: &quot;Add to Cart&quot;,
            &quot;added_to_cart&quot;: &quot;Added to Cart!&quot;,
            &quot;lab_certification&quot;: &quot;Lab Certification&quot;,
            &quot;moisture&quot;: &quot;Moisture&quot;,
            &quot;hmf&quot;: &quot;HMF&quot;,
            &quot;diastase&quot;: &quot;Diastase&quot;,
            &quot;customer_reviews&quot;: &quot;Customer Reviews&quot;,
            &quot;reviews_label&quot;: &quot;Reviews&quot;,
            &quot;reviews_count_label&quot;: &quot;reviews&quot;,
            &quot;write_review&quot;: &quot;Write a Review&quot;,
            &quot;your_name&quot;: &quot;Your Name&quot;,
            &quot;your_email&quot;: &quot;Your Email&quot;,
            &quot;your_rating&quot;: &quot;Your Rating&quot;,
            &quot;your_review&quot;: &quot;Your Review&quot;,
            &quot;submit_review&quot;: &quot;Submit Review&quot;,
            &quot;review_success&quot;: &quot;Thank you for your review!&quot;
        },
        &quot;events&quot;: {
            &quot;events&quot;: &quot;Upcoming Events&quot;,
            &quot;description&quot;: &quot;Workshops, seminars, and field days to grow your beekeeping skills.&quot;,
            &quot;no_events_title&quot;: &quot;No upcoming events&quot;,
            &quot;no_events_desc&quot;: &quot;Check back later for new events.&quot;,
            &quot;join_event&quot;: &quot;Join Event&quot;,
            &quot;spots_left&quot;: &quot;spots left&quot;,
            &quot;full&quot;: &quot;Full&quot;,
            &quot;enrolled&quot;: &quot;Enrolled&quot;,
            &quot;free&quot;: &quot;Free&quot;,
            &quot;paid&quot;: &quot;Paid&quot;,
            &quot;covers&quot;: &quot;Covers&quot;,
            &quot;for&quot;: &quot;For&quot;,
            &quot;beekeepers_only&quot;: &quot;Beekeepers Only&quot;,
            &quot;public_anyone&quot;: &quot;Open to Public&quot;,
            &quot;business_consumers&quot;: &quot;Business Consumers&quot;,
            &quot;hcl&quot;: &quot;HCL Members&quot;,
            &quot;all_registered&quot;: &quot;All Registered&quot;,
            &quot;enroll_btn&quot;: &quot;Enroll Now&quot;,
            &quot;confirm_enrollment&quot;: &quot;Confirm Enrollment&quot;,
            &quot;provide_details&quot;: &quot;Please provide your details&quot;,
            &quot;full_name&quot;: &quot;Full Name&quot;,
            &quot;email_address&quot;: &quot;Email Address&quot;,
            &quot;phone_number&quot;: &quot;Phone Number&quot;
        },
        &quot;membership&quot;: {
            &quot;title_part1&quot;: &quot;Membership&quot;,
            &quot;title_part2&quot;: &quot;Plans&quot;,
            &quot;subtitle&quot;: &quot;Unlock premium benefits and exclusive discounts on certified honey.&quot;,
            &quot;most_popular&quot;: &quot;Most Popular&quot;,
            &quot;subscribe_now&quot;: &quot;Subscribe Now&quot;,
            &quot;processing&quot;: &quot;Processing...&quot;
        },
        &quot;trainings&quot;: {
            &quot;training_title&quot;: &quot;Training Sessions&quot;,
            &quot;description&quot;: &quot;Book expert-led sessions to sharpen your beekeeping and honey processing skills.&quot;,
            &quot;sessions_title&quot;: &quot;Available Sessions&quot;,
            &quot;no_sessions_title&quot;: &quot;No sessions available&quot;,
            &quot;no_sessions_desc&quot;: &quot;Check back later for new training sessions.&quot;,
            &quot;book_now&quot;: &quot;Book Now&quot;,
            &quot;booked&quot;: &quot;Booked&quot;,
            &quot;book_session&quot;: &quot;Book Training Session&quot;,
            &quot;choose_date_desc&quot;: &quot;Choose your preferred date for the session.&quot;,
            &quot;proposed_date&quot;: &quot;Proposed Date&quot;,
            &quot;submit_booking&quot;: &quot;Submit Booking&quot;
        },
        &quot;register&quot;: {
            &quot;title&quot;: &quot;Join Honey Club&quot;,
            &quot;subtitle&quot;: &quot;Register as a beekeeper to access markets, training, and more.&quot;,
            &quot;personal_info&quot;: &quot;Personal Information&quot;,
            &quot;your_location&quot;: &quot;Your Location&quot;,
            &quot;login_credentials&quot;: &quot;Login Credentials&quot;,
            &quot;first_name&quot;: &quot;First Name&quot;,
            &quot;last_name&quot;: &quot;Last Name&quot;,
            &quot;gender&quot;: &quot;Gender&quot;,
            &quot;select_gender&quot;: &quot;Select Gender&quot;,
            &quot;male&quot;: &quot;Male&quot;,
            &quot;female&quot;: &quot;Female&quot;,
            &quot;other&quot;: &quot;Other&quot;,
            &quot;dob&quot;: &quot;Date of Birth&quot;,
            &quot;mobile_number&quot;: &quot;Mobile Number&quot;,
            &quot;mobile_placeholder&quot;: &quot;+255 7XX XXX XXX&quot;,
            &quot;email_address&quot;: &quot;Email Address&quot;,
            &quot;optional&quot;: &quot;(Optional)&quot;,
            &quot;region&quot;: &quot;Region&quot;,
            &quot;select_region&quot;: &quot;Select Region&quot;,
            &quot;district&quot;: &quot;District&quot;,
            &quot;select_district&quot;: &quot;Select District&quot;,
            &quot;ward&quot;: &quot;Ward&quot;,
            &quot;select_ward&quot;: &quot;Select Ward&quot;,
            &quot;village&quot;: &quot;Village&quot;,
            &quot;password&quot;: &quot;Password&quot;,
            &quot;in_coop&quot;: &quot;Part of a Cooperative&quot;,
            &quot;independent&quot;: &quot;Independent Beekeeper&quot;,
            &quot;continue&quot;: &quot;Continue&quot;,
            &quot;back&quot;: &quot;Back&quot;,
            &quot;submit_app&quot;: &quot;Submit Application&quot;,
            &quot;processing&quot;: &quot;Processing...&quot;,
            &quot;already_account&quot;: &quot;Already have an account?&quot;,
            &quot;sign_in&quot;: &quot;Sign In&quot;,
            &quot;what_next_title&quot;: &quot;What happens next?&quot;,
            &quot;what_next_desc&quot;: &quot;Our team will review your application and contact you within 48 hours.&quot;,
            &quot;step1_title&quot;: &quot;Personal Info&quot;,
            &quot;step1_desc&quot;: &quot;Tell us about yourself&quot;,
            &quot;step2_title&quot;: &quot;Location &amp; Credentials&quot;,
            &quot;step2_desc&quot;: &quot;Your region and login details&quot;
        },
        &quot;beekeeper_dashboard&quot;: {
            &quot;welcome_back&quot;: &quot;Welcome Back&quot;,
            &quot;welcome_desc&quot;: &quot;Here&#039;s an overview of your beekeeping operations.&quot;,
            &quot;total_hives&quot;: &quot;Total Hives&quot;,
            &quot;active_hives&quot;: &quot;Active Hives&quot;,
            &quot;certified_harvests&quot;: &quot;Certified Harvests&quot;,
            &quot;pending_inspection&quot;: &quot;Pending Inspection&quot;,
            &quot;average_rating&quot;: &quot;Average Rating&quot;,
            &quot;quality_rating&quot;: &quot;Quality Rating&quot;,
            &quot;recent_harvests&quot;: &quot;Recent Harvests&quot;,
            &quot;no_harvests&quot;: &quot;No harvests yet&quot;,
            &quot;no_harvests_desc&quot;: &quot;Register your hives and submit your first batch.&quot;,
            &quot;view_all&quot;: &quot;View All&quot;,
            &quot;batch_id&quot;: &quot;Batch ID&quot;,
            &quot;weight&quot;: &quot;Weight&quot;,
            &quot;date&quot;: &quot;Date&quot;,
            &quot;status&quot;: &quot;Status&quot;,
            &quot;status_col&quot;: &quot;Status&quot;
        },
        &quot;beekeeper_batches&quot;: {
            &quot;title&quot;: &quot;My Honey Batches&quot;,
            &quot;empty_title&quot;: &quot;No Honey Batches Yet&quot;,
            &quot;empty_desc&quot;: &quot;You haven&#039;t submitted any harvest batches from your hives yet. Start by submitting your first batch below.&quot;,
            &quot;modal_title&quot;: &quot;Submit New Harvest Batch&quot;,
            &quot;registered_product&quot;: &quot;Registered Product&quot;,
            &quot;hcl_rating&quot;: &quot;HCL Quality Rating&quot;,
            &quot;hive&quot;: &quot;Hive&quot;,
            &quot;submit_first&quot;: &quot;Submit your first harvest batch.&quot;,
            &quot;create_batch_btn&quot;: &quot;New Batch&quot;,
            &quot;batch_code&quot;: &quot;Batch Code&quot;,
            &quot;honey_type&quot;: &quot;Honey Type&quot;,
            &quot;weight&quot;: &quot;Weight (kg)&quot;,
            &quot;harvest_date&quot;: &quot;Harvest Date&quot;,
            &quot;status&quot;: &quot;Status&quot;,
            &quot;submitted_on&quot;: &quot;Submitted On&quot;,
            &quot;new_batch_title&quot;: &quot;Submit New Harvest Batch&quot;,
            &quot;source_hive&quot;: &quot;Source Hive&quot;,
            &quot;select_hive&quot;: &quot;Select hive&quot;,
            &quot;select_product&quot;: &quot;Select product&quot;,
            &quot;unknown_product&quot;: &quot;Unknown Product&quot;,
            &quot;submit_btn&quot;: &quot;Submit Batch&quot;,
            &quot;status_harvested_msg&quot;: &quot;Harvested &mdash; awaiting collection&quot;,
            &quot;status_testing_msg&quot;: &quot;In testing at HCL labs&quot;,
            &quot;no_batches&quot;: &quot;No batches yet&quot;
        },
        &quot;beekeeper_products&quot;: {
            &quot;title&quot;: &quot;My Products&quot;,
            &quot;empty_title&quot;: &quot;No products registered&quot;,
            &quot;empty_desc&quot;: &quot;Register the honey types you produce.&quot;,
            &quot;modal_title&quot;: &quot;Select Honey Type&quot;,
            &quot;modal_desc&quot;: &quot;Choose the honey type you produce.&quot;,
            &quot;select_btn&quot;: &quot;Register&quot;,
            &quot;edit_btn&quot;: &quot;Edit&quot;,
            &quot;save_btn&quot;: &quot;Save&quot;,
            &quot;registered_at&quot;: &quot;Registered&quot;
        },
        &quot;beekeeper_profile&quot;: {
            &quot;personal_info&quot;: &quot;Personal Information&quot;,
            &quot;first_name&quot;: &quot;First Name&quot;,
            &quot;last_name&quot;: &quot;Last Name&quot;,
            &quot;email&quot;: &quot;Email&quot;,
            &quot;gender&quot;: &quot;Gender&quot;,
            &quot;education&quot;: &quot;Education&quot;,
            &quot;experience&quot;: &quot;Experience&quot;,
            &quot;yrs_exp&quot;: &quot;years experience&quot;,
            &quot;years&quot;: &quot;years&quot;,
            &quot;colonies&quot;: &quot;Colonies&quot;,
            &quot;capacity_kg&quot;: &quot;Capacity (kg)&quot;,
            &quot;land_acres&quot;: &quot;Land (acres)&quot;,
            &quot;cooperative&quot;: &quot;Cooperative&quot;,
            &quot;training&quot;: &quot;Training Level&quot;,
            &quot;location&quot;: &quot;Location&quot;,
            &quot;region&quot;: &quot;Region&quot;,
            &quot;district&quot;: &quot;District&quot;,
            &quot;ward&quot;: &quot;Ward&quot;,
            &quot;village&quot;: &quot;Village&quot;,
            &quot;save_changes&quot;: &quot;Save Changes&quot;,
            &quot;under_review&quot;: &quot;Under Review&quot;,
            &quot;under_review_desc&quot;: &quot;Your application is being reviewed. We will notify you soon.&quot;,
            &quot;survey_upcoming&quot;: &quot;Survey Scheduled&quot;,
            &quot;survey_upcoming_desc&quot;: &quot;A field officer survey has been scheduled for your apiary.&quot;
        },
        &quot;hives&quot;: {
            &quot;my_hives&quot;: &quot;My Hives&quot;,
            &quot;no_hives&quot;: &quot;No hives registered&quot;,
            &quot;register_first&quot;: &quot;Register your first hive to start managing your apiary.&quot;,
            &quot;register_new&quot;: &quot;Register New Hive&quot;,
            &quot;hive_name&quot;: &quot;Hive Name&quot;,
            &quot;hive_name_placeholder&quot;: &quot;e.g. Hive Alpha&quot;,
            &quot;hive_type&quot;: &quot;Hive Type&quot;,
            &quot;type_langstroth&quot;: &quot;Langstroth&quot;,
            &quot;type_topbar&quot;: &quot;Top Bar&quot;,
            &quot;type_moveable&quot;: &quot;Moveable Frame&quot;,
            &quot;frames&quot;: &quot;Number of Frames&quot;,
            &quot;gps_lat&quot;: &quot;GPS Latitude&quot;,
            &quot;gps_lon&quot;: &quot;GPS Longitude&quot;,
            &quot;submit_approval&quot;: &quot;Submit for Approval&quot;
        },
        &quot;documents&quot;: {
            &quot;title&quot;: &quot;My Documents&quot;,
            &quot;upload_id_document&quot;: &quot;Upload ID Document&quot;,
            &quot;id_type&quot;: &quot;ID Type&quot;,
            &quot;id_type_national&quot;: &quot;National ID (NIDA)&quot;,
            &quot;id_type_passport&quot;: &quot;Passport&quot;,
            &quot;id_type_voter&quot;: &quot;Voter ID&quot;,
            &quot;id_number&quot;: &quot;ID Number&quot;,
            &quot;id_number_placeholder&quot;: &quot;Enter your ID number&quot;,
            &quot;document_photo&quot;: &quot;Document Photo&quot;,
            &quot;submit_document&quot;: &quot;Submit Document&quot;,
            &quot;document_history&quot;: &quot;Document History&quot;,
            &quot;fully_verified_msg&quot;: &quot;Your identity is fully verified. ✅&quot;,
            &quot;pending_verification_msg&quot;: &quot;Your document is pending verification.&quot;,
            &quot;verification_rejected_msg&quot;: &quot;Your document was rejected.&quot;,
            &quot;registration_success_msg&quot;: &quot;Document submitted successfully!&quot;,
            &quot;rejection&quot;: &quot;Rejection Details&quot;,
            &quot;reason&quot;: &quot;Reason&quot;,
            &quot;reason_unclear&quot;: &quot;Document is unclear or unreadable&quot;,
            &quot;upload_clearer_doc&quot;: &quot;Upload a clearer document&quot;
        },
        &quot;consumer_dashboard&quot;: {
            &quot;welcome&quot;: &quot;Welcome&quot;,
            &quot;description&quot;: &quot;Your honey shopping hub. Track orders, memberships, and explore more.&quot;,
            &quot;total_orders&quot;: &quot;Total Orders&quot;,
            &quot;total_spent&quot;: &quot;Total Spent&quot;,
            &quot;pending_orders&quot;: &quot;Pending Orders&quot;,
            &quot;member&quot;: &quot;Member&quot;,
            &quot;active&quot;: &quot;Active&quot;,
            &quot;active_until&quot;: &quot;Active until&quot;,
            &quot;not_member_msg&quot;: &quot;Not yet a member&quot;,
            &quot;view_membership_tiers&quot;: &quot;View Membership Plans&quot;,
            &quot;view_orders&quot;: &quot;View Orders&quot;,
            &quot;browse_shop&quot;: &quot;Browse Shop&quot;
        },
        &quot;consumer_profile&quot;: {
            &quot;title&quot;: &quot;My Profile&quot;,
            &quot;first_name&quot;: &quot;First Name&quot;,
            &quot;last_name&quot;: &quot;Last Name&quot;,
            &quot;email&quot;: &quot;Email&quot;,
            &quot;save_changes&quot;: &quot;Save Changes&quot;
        },
        &quot;my_orders&quot;: {
            &quot;title&quot;: &quot;My Orders&quot;,
            &quot;no_orders_title&quot;: &quot;No orders yet&quot;,
            &quot;no_orders_desc&quot;: &quot;Place your first order from our shop.&quot;,
            &quot;browse_shop&quot;: &quot;Browse Shop&quot;,
            &quot;col_order&quot;: &quot;Order #&quot;,
            &quot;col_date&quot;: &quot;Date&quot;,
            &quot;col_items&quot;: &quot;Items&quot;,
            &quot;col_total&quot;: &quot;Total&quot;,
            &quot;col_status&quot;: &quot;Status&quot;,
            &quot;items_count&quot;: &quot;items&quot;
        },
        &quot;my_events&quot;: {
            &quot;title&quot;: &quot;My Events&quot;,
            &quot;no_events_title&quot;: &quot;No enrolled events&quot;,
            &quot;no_events_desc&quot;: &quot;Browse events and enroll in upcoming ones.&quot;,
            &quot;browse_events&quot;: &quot;Browse Events&quot;,
            &quot;enrolled_on&quot;: &quot;Enrolled on&quot;
        },
        &quot;my_trainings&quot;: {
            &quot;title&quot;: &quot;My Training Bookings&quot;,
            &quot;no_bookings_title&quot;: &quot;No bookings yet&quot;,
            &quot;no_bookings_desc&quot;: &quot;Browse available training sessions.&quot;,
            &quot;browse_sessions&quot;: &quot;Browse Sessions&quot;,
            &quot;browse_btn&quot;: &quot;Browse Training&quot;,
            &quot;proposed_date&quot;: &quot;Proposed Date&quot;,
            &quot;confirmed_date&quot;: &quot;Confirmed Date&quot;,
            &quot;rescheduled_to&quot;: &quot;Rescheduled to&quot;,
            &quot;admin_notes&quot;: &quot;Admin Notes&quot;
        },
        &quot;surveys&quot;: {
            &quot;no_surveys_title&quot;: &quot;No field surveys&quot;,
            &quot;no_surveys_desc&quot;: &quot;Field surveys will appear here when scheduled.&quot;,
            &quot;scheduled_date&quot;: &quot;Scheduled Date&quot;,
            &quot;scheduled_by&quot;: &quot;Scheduled By&quot;,
            &quot;proposed_date&quot;: &quot;Proposed Date&quot;,
            &quot;action_required&quot;: &quot;Action Required&quot;,
            &quot;waiting_for_admin&quot;: &quot;Waiting for Admin&quot;,
            &quot;admin_notes&quot;: &quot;Admin Notes&quot;,
            &quot;accept_date_btn&quot;: &quot;Accept Date&quot;,
            &quot;req_reschedule_btn&quot;: &quot;Request Reschedule&quot;,
            &quot;accept_modal_title&quot;: &quot;Accept Survey Date&quot;,
            &quot;accept_modal_desc&quot;: &quot;Please confirm you are available on the scheduled date.&quot;,
            &quot;req_reschedule_modal_title&quot;: &quot;Request Reschedule&quot;,
            &quot;reason_label&quot;: &quot;Reason&quot;,
            &quot;reason_placeholder&quot;: &quot;Why do you need a different date?&quot;,
            &quot;new_date_label&quot;: &quot;Preferred New Date&quot;,
            &quot;submit_req_btn&quot;: &quot;Submit Request&quot;,
            &quot;your_reschedule_req&quot;: &quot;Your Reschedule Request&quot;,
            &quot;reason&quot;: &quot;Reason&quot;,
            &quot;on&quot;: &quot;on&quot;,
            &quot;not_set&quot;: &quot;Not set&quot;,
            &quot;conducted_by&quot;: &quot;Conducted By&quot;,
            &quot;completion_details&quot;: &quot;Completion Details&quot;
        },
        &quot;ratings&quot;: {
            &quot;title&quot;: &quot;My Star Ratings&quot;,
            &quot;no_ratings_title&quot;: &quot;No ratings yet&quot;,
            &quot;no_ratings_desc&quot;: &quot;Your quality ratings will appear here after lab testing.&quot;,
            &quot;rated_batches_title&quot;: &quot;Rated Batches&quot;,
            &quot;rated_batches_count&quot;: &quot;batches&quot;,
            &quot;kg&quot;: &quot;kg&quot;
        }
    },
    &quot;genders&quot;: [
        &quot;Male&quot;,
        &quot;Female&quot;
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-splash" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-splash"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-splash"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-splash" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-splash">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-splash" data-method="GET"
      data-path="api/v1/portal/splash"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-splash', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-splash"
                    onclick="tryItOut('GETapi-v1-portal-splash');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-splash"
                    onclick="cancelTryOut('GETapi-v1-portal-splash');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-splash"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/splash</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-splash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-splash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-voyages-search">Search for available voyages.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-voyages-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/voyages/search" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"route_id\": \"architecto\",
    \"departure_date\": \"2052-09-16\",
    \"passengers\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/voyages/search"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "route_id": "architecto",
    "departure_date": "2052-09-16",
    "passengers": 22
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-voyages-search">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Server Error&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-voyages-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-voyages-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-voyages-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-voyages-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-voyages-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-voyages-search" data-method="GET"
      data-path="api/v1/portal/voyages/search"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-voyages-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-voyages-search"
                    onclick="tryItOut('GETapi-v1-portal-voyages-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-voyages-search"
                    onclick="cancelTryOut('GETapi-v1-portal-voyages-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-voyages-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/voyages/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-voyages-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-voyages-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>route_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="route_id"                data-endpoint="GETapi-v1-portal-voyages-search"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>departure_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="departure_date"                data-endpoint="GETapi-v1-portal-voyages-search"
               value="2052-09-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>today</code>. Example: <code>2052-09-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>passengers</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers"                data-endpoint="GETapi-v1-portal-voyages-search"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 50. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-portal-payment-methods">GET api/v1/portal/payment-methods</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-payment-methods">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/payment-methods" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/payment-methods"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-payment-methods">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;cash&quot;,
            &quot;name&quot;: &quot;Cash at Window&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;slug&quot;: &quot;crdb_bank&quot;,
            &quot;name&quot;: &quot;CRDB Bank Gateway&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;slug&quot;: &quot;nmb_bank&quot;,
            &quot;name&quot;: &quot;NMB Bank Gateway&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;slug&quot;: &quot;mpesa&quot;,
            &quot;name&quot;: &quot;Vodacom M-Pesa&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;slug&quot;: &quot;tigopesa&quot;,
            &quot;name&quot;: &quot;Tigo Pesa&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;slug&quot;: &quot;airtel_money&quot;,
            &quot;name&quot;: &quot;Airtel Money&quot;
        },
        {
            &quot;id&quot;: 7,
            &quot;slug&quot;: &quot;halopesa&quot;,
            &quot;name&quot;: &quot;Halopesa&quot;
        },
        {
            &quot;id&quot;: 8,
            &quot;slug&quot;: &quot;visa_mastercard&quot;,
            &quot;name&quot;: &quot;Visa / Mastercard (Stripe/CyberSource)&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-payment-methods" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-payment-methods"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-payment-methods"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-payment-methods" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-payment-methods">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-payment-methods" data-method="GET"
      data-path="api/v1/portal/payment-methods"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-payment-methods', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-payment-methods"
                    onclick="tryItOut('GETapi-v1-portal-payment-methods');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-payment-methods"
                    onclick="cancelTryOut('GETapi-v1-portal-payment-methods');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-payment-methods"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/payment-methods</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-payment-methods"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-dashboard-metrics">Original metrics endpoint — preserved for backward compatibility.</h2>

<p>
</p>

<p>New frontend will call commandCenter() instead.</p>

<span id="example-requests-GETapi-v1-portal-dashboard-metrics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/dashboard/metrics" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/dashboard/metrics"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-dashboard-metrics">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-dashboard-metrics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-dashboard-metrics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-dashboard-metrics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-dashboard-metrics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-dashboard-metrics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-dashboard-metrics" data-method="GET"
      data-path="api/v1/portal/dashboard/metrics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-dashboard-metrics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-dashboard-metrics"
                    onclick="tryItOut('GETapi-v1-portal-dashboard-metrics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-dashboard-metrics"
                    onclick="cancelTryOut('GETapi-v1-portal-dashboard-metrics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-dashboard-metrics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/dashboard/metrics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-dashboard-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-dashboard-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-dashboard-command-center">Operational Command Center — structured dashboard intelligence.</h2>

<p>
</p>

<p>Returns role-specific data:</p>
<ul>
<li>Sales Officer: shift context + attention items + voyage board + session feed</li>
<li>Travel Agent: wallet health + agency KPIs + associate leaderboard + attention items + voyage board</li>
<li>Associate Agent: parent wallet + personal performance + attention items + voyage board</li>
</ul>

<span id="example-requests-GETapi-v1-portal-dashboard-command-center">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/dashboard/command-center" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/dashboard/command-center"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-dashboard-command-center">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-dashboard-command-center" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-dashboard-command-center"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-dashboard-command-center"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-dashboard-command-center" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-dashboard-command-center">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-dashboard-command-center" data-method="GET"
      data-path="api/v1/portal/dashboard/command-center"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-dashboard-command-center', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-dashboard-command-center"
                    onclick="tryItOut('GETapi-v1-portal-dashboard-command-center');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-dashboard-command-center"
                    onclick="cancelTryOut('GETapi-v1-portal-dashboard-command-center');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-dashboard-command-center"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/dashboard/command-center</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-dashboard-command-center"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-dashboard-command-center"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-portal-shift-overtime-request">POST api/v1/portal/shift/overtime-request</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-shift-overtime-request">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/shift/overtime-request" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"hours\": 4,
    \"reason\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/shift/overtime-request"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "hours": 4,
    "reason": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-shift-overtime-request">
</span>
<span id="execution-results-POSTapi-v1-portal-shift-overtime-request" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-shift-overtime-request"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-shift-overtime-request"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-shift-overtime-request" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-shift-overtime-request">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-shift-overtime-request" data-method="POST"
      data-path="api/v1/portal/shift/overtime-request"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-shift-overtime-request', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-shift-overtime-request"
                    onclick="tryItOut('POSTapi-v1-portal-shift-overtime-request');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-shift-overtime-request"
                    onclick="cancelTryOut('POSTapi-v1-portal-shift-overtime-request');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-shift-overtime-request"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/shift/overtime-request</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-shift-overtime-request"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-shift-overtime-request"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>hours</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="hours"                data-endpoint="POSTapi-v1-portal-shift-overtime-request"
               value="4"
               data-component="body">
    <br>
<p>Must be at least 0.5. Must not be greater than 12. Example: <code>4</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-portal-shift-overtime-request"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-portal-me">Get the authenticated user&#039;s profile, domain, roles, permissions, and wallet balance for the Vue 3 SPA.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-me" data-method="GET"
      data-path="api/v1/portal/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-me"
                    onclick="tryItOut('GETapi-v1-portal-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-me"
                    onclick="cancelTryOut('GETapi-v1-portal-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-dashboard">GET /api/v1/portal/dashboard</h2>

<p>
</p>

<p>Returns live KPIs and departure pipeline for the Sales Portal.
Data is scoped per user domain:</p>
<ul>
<li>associate → only their own bookings</li>
<li>agent    → their own + all associate bookings under their agency</li>
<li>system_user (sales_officer etc.) → all bookings</li>
</ul>

<span id="example-requests-GETapi-v1-portal-dashboard">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/dashboard" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/dashboard"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-dashboard">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-dashboard" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-dashboard"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-dashboard"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-dashboard" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-dashboard">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-dashboard" data-method="GET"
      data-path="api/v1/portal/dashboard"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-dashboard', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-dashboard"
                    onclick="tryItOut('GETapi-v1-portal-dashboard');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-dashboard"
                    onclick="cancelTryOut('GETapi-v1-portal-dashboard');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-dashboard"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/dashboard</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-dashboard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-dashboard"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-wallet">Get the agent&#039;s wallet summary.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-wallet">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/wallet" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/wallet"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-wallet">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-wallet" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-wallet"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-wallet"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-wallet" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-wallet">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-wallet" data-method="GET"
      data-path="api/v1/portal/wallet"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-wallet', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-wallet"
                    onclick="tryItOut('GETapi-v1-portal-wallet');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-wallet"
                    onclick="cancelTryOut('GETapi-v1-portal-wallet');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-wallet"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/wallet</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-wallet"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-wallet"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-portal-wallet-top-up">Request a wallet top-up.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-wallet-top-up">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/wallet/top-up" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"amount\": 635,
    \"payment_method\": \"bank_transfer\",
    \"reference\": \"n\",
    \"notes\": \"g\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/wallet/top-up"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "amount": 635,
    "payment_method": "bank_transfer",
    "reference": "n",
    "notes": "g"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-wallet-top-up">
</span>
<span id="execution-results-POSTapi-v1-portal-wallet-top-up" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-wallet-top-up"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-wallet-top-up"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-wallet-top-up" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-wallet-top-up">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-wallet-top-up" data-method="POST"
      data-path="api/v1/portal/wallet/top-up"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-wallet-top-up', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-wallet-top-up"
                    onclick="tryItOut('POSTapi-v1-portal-wallet-top-up');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-wallet-top-up"
                    onclick="cancelTryOut('POSTapi-v1-portal-wallet-top-up');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-wallet-top-up"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/wallet/top-up</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="amount"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="635"
               data-component="body">
    <br>
<p>Must be at least 1000. Example: <code>635</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>payment_method</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="payment_method"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="bank_transfer"
               data-component="body">
    <br>
<p>Example: <code>bank_transfer</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>bank_transfer</code></li> <li><code>mobile_money</code></li> <li><code>cash</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reference"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-v1-portal-wallet-top-up"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>g</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-portal-wallet-transactions">Get wallet transactions history.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-wallet-transactions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/wallet/transactions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/wallet/transactions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-wallet-transactions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-wallet-transactions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-wallet-transactions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-wallet-transactions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-wallet-transactions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-wallet-transactions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-wallet-transactions" data-method="GET"
      data-path="api/v1/portal/wallet/transactions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-wallet-transactions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-wallet-transactions"
                    onclick="tryItOut('GETapi-v1-portal-wallet-transactions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-wallet-transactions"
                    onclick="cancelTryOut('GETapi-v1-portal-wallet-transactions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-wallet-transactions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/wallet/transactions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-wallet-transactions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-wallet-transactions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-pending-refunds">GET api/v1/portal/pending-refunds</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-pending-refunds">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/pending-refunds" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/pending-refunds"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-pending-refunds">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-pending-refunds" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-pending-refunds"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-pending-refunds"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-pending-refunds" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-pending-refunds">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-pending-refunds" data-method="GET"
      data-path="api/v1/portal/pending-refunds"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-pending-refunds', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-pending-refunds"
                    onclick="tryItOut('GETapi-v1-portal-pending-refunds');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-pending-refunds"
                    onclick="cancelTryOut('GETapi-v1-portal-pending-refunds');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-pending-refunds"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/pending-refunds</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-pending-refunds"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-pending-refunds"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports">Get the Sales &amp; Commission Report data.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports" data-method="GET"
      data-path="api/v1/portal/reports"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports"
                    onclick="tryItOut('GETapi-v1-portal-reports');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports"
                    onclick="cancelTryOut('GETapi-v1-portal-reports');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-filters">GET api/v1/portal/reports/filters</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-filters">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/filters" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/filters"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-filters">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-filters" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-filters"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-filters"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-filters" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-filters">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-filters" data-method="GET"
      data-path="api/v1/portal/reports/filters"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-filters', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-filters"
                    onclick="tryItOut('GETapi-v1-portal-reports-filters');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-filters"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-filters');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-filters"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/filters</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-filters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-filters"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-bookings">GET api/v1/portal/reports/bookings</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-bookings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/bookings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/bookings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-bookings">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-bookings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-bookings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-bookings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-bookings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-bookings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-bookings" data-method="GET"
      data-path="api/v1/portal/reports/bookings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-bookings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-bookings"
                    onclick="tryItOut('GETapi-v1-portal-reports-bookings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-bookings"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-bookings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-bookings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/bookings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-bookings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-bookings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-cancellations">GET api/v1/portal/reports/cancellations</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-cancellations">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/cancellations" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/cancellations"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-cancellations">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-cancellations" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-cancellations"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-cancellations"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-cancellations" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-cancellations">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-cancellations" data-method="GET"
      data-path="api/v1/portal/reports/cancellations"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-cancellations', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-cancellations"
                    onclick="tryItOut('GETapi-v1-portal-reports-cancellations');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-cancellations"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-cancellations');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-cancellations"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/cancellations</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-cancellations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-cancellations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-float-ledger">GET api/v1/portal/reports/float-ledger</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-float-ledger">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/float-ledger" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/float-ledger"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-float-ledger">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-float-ledger" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-float-ledger"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-float-ledger"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-float-ledger" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-float-ledger">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-float-ledger" data-method="GET"
      data-path="api/v1/portal/reports/float-ledger"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-float-ledger', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-float-ledger"
                    onclick="tryItOut('GETapi-v1-portal-reports-float-ledger');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-float-ledger"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-float-ledger');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-float-ledger"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/float-ledger</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-float-ledger"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-float-ledger"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-manifest">GET api/v1/portal/reports/manifest</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-manifest">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/manifest" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/manifest"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-manifest">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-manifest" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-manifest"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-manifest"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-manifest" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-manifest">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-manifest" data-method="GET"
      data-path="api/v1/portal/reports/manifest"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-manifest', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-manifest"
                    onclick="tryItOut('GETapi-v1-portal-reports-manifest');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-manifest"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-manifest');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-manifest"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/manifest</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-manifest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-manifest"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-commission-reconciliation">GET api/v1/portal/reports/commission-reconciliation</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-commission-reconciliation">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/commission-reconciliation" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/commission-reconciliation"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-commission-reconciliation">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-commission-reconciliation" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-commission-reconciliation"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-commission-reconciliation"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-commission-reconciliation" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-commission-reconciliation">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-commission-reconciliation" data-method="GET"
      data-path="api/v1/portal/reports/commission-reconciliation"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-commission-reconciliation', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-commission-reconciliation"
                    onclick="tryItOut('GETapi-v1-portal-reports-commission-reconciliation');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-commission-reconciliation"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-commission-reconciliation');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-commission-reconciliation"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/commission-reconciliation</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-commission-reconciliation"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-commission-reconciliation"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-peak-hours">GET api/v1/portal/reports/peak-hours</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-peak-hours">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/peak-hours" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/peak-hours"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-peak-hours">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-peak-hours" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-peak-hours"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-peak-hours"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-peak-hours" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-peak-hours">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-peak-hours" data-method="GET"
      data-path="api/v1/portal/reports/peak-hours"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-peak-hours', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-peak-hours"
                    onclick="tryItOut('GETapi-v1-portal-reports-peak-hours');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-peak-hours"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-peak-hours');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-peak-hours"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/peak-hours</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-peak-hours"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-peak-hours"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-portal-reports-cancellation-trends">GET api/v1/portal/reports/cancellation-trends</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-reports-cancellation-trends">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/reports/cancellation-trends" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/reports/cancellation-trends"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-reports-cancellation-trends">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-reports-cancellation-trends" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-reports-cancellation-trends"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-reports-cancellation-trends"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-reports-cancellation-trends" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-reports-cancellation-trends">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-reports-cancellation-trends" data-method="GET"
      data-path="api/v1/portal/reports/cancellation-trends"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-reports-cancellation-trends', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-reports-cancellation-trends"
                    onclick="tryItOut('GETapi-v1-portal-reports-cancellation-trends');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-reports-cancellation-trends"
                    onclick="cancelTryOut('GETapi-v1-portal-reports-cancellation-trends');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-reports-cancellation-trends"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/reports/cancellation-trends</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-reports-cancellation-trends"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-reports-cancellation-trends"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-portal-coupons-validate">Validate a coupon code and preview discount.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-coupons-validate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/coupons/validate" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"code\": \"b\",
    \"amount\": 39,
    \"currency\": \"gzm\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/coupons/validate"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "code": "b",
    "amount": 39,
    "currency": "gzm"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-coupons-validate">
</span>
<span id="execution-results-POSTapi-v1-portal-coupons-validate" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-coupons-validate"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-coupons-validate"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-coupons-validate" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-coupons-validate">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-coupons-validate" data-method="POST"
      data-path="api/v1/portal/coupons/validate"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-coupons-validate', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-coupons-validate"
                    onclick="tryItOut('POSTapi-v1-portal-coupons-validate');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-coupons-validate"
                    onclick="cancelTryOut('POSTapi-v1-portal-coupons-validate');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-coupons-validate"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/coupons/validate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-coupons-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-coupons-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-v1-portal-coupons-validate"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="amount"                data-endpoint="POSTapi-v1-portal-coupons-validate"
               value="39"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>39</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-portal-coupons-validate"
               value="gzm"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>gzm</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-portal-announcements">Get active announcements for the portal.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-announcements">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/announcements" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/announcements"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-announcements">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-announcements" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-announcements"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-announcements"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-announcements" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-announcements">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-announcements" data-method="GET"
      data-path="api/v1/portal/announcements"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-announcements', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-announcements"
                    onclick="tryItOut('GETapi-v1-portal-announcements');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-announcements"
                    onclick="cancelTryOut('GETapi-v1-portal-announcements');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-announcements"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/announcements</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-announcements"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-announcements"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-mobile-splash">Provide bootstrap data for the mobile app.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mobile-splash">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/mobile/splash" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/splash"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-mobile-splash">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;routes&quot;: [
        {
            &quot;departure_port&quot;: {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;Dar es Salaam Port&quot;,
                &quot;code&quot;: null
            },
            &quot;arrival_port&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Zanzibar&quot;,
                &quot;code&quot;: null
            },
            &quot;display_name&quot;: &quot;Dar es Salaam to Zanzibar Express&quot;,
            &quot;color&quot;: &quot;#FF6B35&quot;
        },
        {
            &quot;departure_port&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Zanzibar&quot;,
                &quot;code&quot;: null
            },
            &quot;arrival_port&quot;: {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;Dar es Salaam Port&quot;,
                &quot;code&quot;: null
            },
            &quot;display_name&quot;: &quot;Zanzibar to Dar es Salaam Express&quot;,
            &quot;color&quot;: &quot;#FF6B35&quot;
        },
        {
            &quot;departure_port&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Zanzibar&quot;,
                &quot;code&quot;: null
            },
            &quot;arrival_port&quot;: {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Mkoani Port Pemba&quot;,
                &quot;code&quot;: null
            },
            &quot;display_name&quot;: &quot;Zanzibar to Pemba Express&quot;,
            &quot;color&quot;: &quot;#FF6B35&quot;
        },
        {
            &quot;departure_port&quot;: {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Mkoani Port Pemba&quot;,
                &quot;code&quot;: null
            },
            &quot;arrival_port&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Zanzibar&quot;,
                &quot;code&quot;: null
            },
            &quot;display_name&quot;: &quot;Pemba to Zanzibar Express&quot;,
            &quot;color&quot;: &quot;#FF6B35&quot;
        },
        {
            &quot;departure_port&quot;: {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Mafia&quot;,
                &quot;code&quot;: null
            },
            &quot;arrival_port&quot;: {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Dar es Salaam&quot;,
                &quot;code&quot;: null
            },
            &quot;display_name&quot;: &quot;Mafia - Dar es Salaam&quot;,
            &quot;color&quot;: &quot;#FF6B35&quot;
        }
    ],
    &quot;identity_types&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Passport&quot;,
            &quot;code&quot;: &quot;passport&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;National ID / NIDA&quot;,
            &quot;code&quot;: &quot;national_id&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Voter Card&quot;,
            &quot;code&quot;: &quot;voters_id&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;name&quot;: &quot;Driving License&quot;,
            &quot;code&quot;: &quot;driving_license&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;name&quot;: &quot;None / Child&quot;,
            &quot;code&quot;: &quot;none&quot;
        }
    ],
    &quot;passenger_types&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Adult&quot;,
            &quot;code&quot;: &quot;adult&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;Child&quot;,
            &quot;code&quot;: &quot;child&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Infant&quot;,
            &quot;code&quot;: &quot;infant&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mobile-splash" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mobile-splash"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mobile-splash"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mobile-splash" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mobile-splash">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mobile-splash" data-method="GET"
      data-path="api/v1/mobile/splash"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mobile-splash', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mobile-splash"
                    onclick="tryItOut('GETapi-v1-mobile-splash');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mobile-splash"
                    onclick="cancelTryOut('GETapi-v1-mobile-splash');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mobile-splash"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mobile/splash</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mobile-splash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-mobile-splash"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-mobile-search">Search availability by departure and arrival port IDs.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mobile-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/mobile/search" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"departure_port\": 16,
    \"arrival_port\": 16,
    \"date\": \"2052-09-16\",
    \"return_date\": \"2052-09-16\",
    \"passengers\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/search"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "departure_port": 16,
    "arrival_port": 16,
    "date": "2052-09-16",
    "return_date": "2052-09-16",
    "passengers": 22
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-mobile-search">
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Validation error&quot;,
    &quot;errors&quot;: {
        &quot;departure_port&quot;: [
            &quot;The selected departure port is invalid.&quot;
        ],
        &quot;arrival_port&quot;: [
            &quot;The selected arrival port is invalid.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mobile-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mobile-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mobile-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mobile-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mobile-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mobile-search" data-method="GET"
      data-path="api/v1/mobile/search"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mobile-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mobile-search"
                    onclick="tryItOut('GETapi-v1-mobile-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mobile-search"
                    onclick="cancelTryOut('GETapi-v1-mobile-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mobile-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mobile/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mobile-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-mobile-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>departure_port</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="departure_port"                data-endpoint="GETapi-v1-mobile-search"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>arrival_port</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="arrival_port"                data-endpoint="GETapi-v1-mobile-search"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date"                data-endpoint="GETapi-v1-mobile-search"
               value="2052-09-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>today</code>. Example: <code>2052-09-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="return_date"                data-endpoint="GETapi-v1-mobile-search"
               value="2052-09-16"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>date</code>. Example: <code>2052-09-16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>passengers</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers"                data-endpoint="GETapi-v1-mobile-search"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 50. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-mobile-seat-layout">Get seat layout for a specific voyage and class.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mobile-seat-layout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/mobile/seat-layout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 16,
    \"class_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/seat-layout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 16,
    "class_id": 16
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-mobile-seat-layout">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;layout&quot;: {
        &quot;version&quot;: 1,
        &quot;rows&quot;: 26,
        &quot;columns&quot;: 5,
        &quot;grid&quot;: [
            [
                &quot;A1&quot;,
                &quot;A2&quot;,
                null,
                &quot;A3&quot;,
                &quot;A4&quot;
            ],
            [
                &quot;B1&quot;,
                &quot;B2&quot;,
                null,
                &quot;B3&quot;,
                &quot;B4&quot;
            ],
            [
                &quot;C1&quot;,
                &quot;C2&quot;,
                null,
                &quot;C3&quot;,
                &quot;C4&quot;
            ],
            [
                &quot;D1&quot;,
                &quot;D2&quot;,
                null,
                &quot;D3&quot;,
                &quot;D4&quot;
            ],
            [
                &quot;E1&quot;,
                &quot;E2&quot;,
                null,
                &quot;E3&quot;,
                &quot;E4&quot;
            ],
            [
                &quot;F1&quot;,
                &quot;F2&quot;,
                null,
                &quot;F3&quot;,
                &quot;F4&quot;
            ],
            [
                &quot;G1&quot;,
                &quot;G2&quot;,
                null,
                &quot;G3&quot;,
                &quot;G4&quot;
            ],
            [
                &quot;H1&quot;,
                &quot;H2&quot;,
                null,
                &quot;H3&quot;,
                &quot;H4&quot;
            ],
            [
                &quot;I1&quot;,
                &quot;I2&quot;,
                null,
                &quot;I3&quot;,
                &quot;I4&quot;
            ],
            [
                &quot;J1&quot;,
                &quot;J2&quot;,
                null,
                &quot;J3&quot;,
                &quot;J4&quot;
            ],
            [
                &quot;K1&quot;,
                &quot;K2&quot;,
                null,
                &quot;K3&quot;,
                &quot;K4&quot;
            ],
            [
                &quot;L1&quot;,
                &quot;L2&quot;,
                null,
                &quot;L3&quot;,
                &quot;L4&quot;
            ],
            [
                &quot;M1&quot;,
                &quot;M2&quot;,
                null,
                &quot;M3&quot;,
                &quot;M4&quot;
            ],
            [
                &quot;N1&quot;,
                &quot;N2&quot;,
                null,
                &quot;N3&quot;,
                &quot;N4&quot;
            ],
            [
                &quot;O1&quot;,
                &quot;O2&quot;,
                null,
                &quot;O3&quot;,
                &quot;O4&quot;
            ],
            [
                &quot;P1&quot;,
                &quot;P2&quot;,
                null,
                &quot;P3&quot;,
                &quot;P4&quot;
            ],
            [
                &quot;Q1&quot;,
                &quot;Q2&quot;,
                null,
                &quot;Q3&quot;,
                &quot;Q4&quot;
            ],
            [
                &quot;R1&quot;,
                &quot;R2&quot;,
                null,
                &quot;R3&quot;,
                &quot;R4&quot;
            ],
            [
                &quot;S1&quot;,
                &quot;S2&quot;,
                null,
                &quot;S3&quot;,
                &quot;S4&quot;
            ],
            [
                &quot;T1&quot;,
                &quot;T2&quot;,
                null,
                &quot;T3&quot;,
                &quot;T4&quot;
            ],
            [
                &quot;U1&quot;,
                &quot;U2&quot;,
                null,
                &quot;U3&quot;,
                &quot;U4&quot;
            ],
            [
                &quot;V1&quot;,
                &quot;V2&quot;,
                null,
                &quot;V3&quot;,
                &quot;V4&quot;
            ],
            [
                &quot;W1&quot;,
                &quot;W2&quot;,
                null,
                &quot;W3&quot;,
                &quot;W4&quot;
            ],
            [
                &quot;X1&quot;,
                &quot;X2&quot;,
                null,
                &quot;X3&quot;,
                &quot;X4&quot;
            ],
            [
                &quot;Y1&quot;,
                &quot;Y2&quot;,
                null,
                &quot;Y3&quot;,
                &quot;Y4&quot;
            ],
            [
                &quot;Z1&quot;,
                &quot;Z2&quot;,
                null,
                &quot;Z3&quot;,
                &quot;Z4&quot;
            ]
        ],
        &quot;sold&quot;: [
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;,
            &quot;OPEN&quot;
        ],
        &quot;held&quot;: [],
        &quot;blocked&quot;: []
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mobile-seat-layout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mobile-seat-layout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mobile-seat-layout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mobile-seat-layout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mobile-seat-layout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mobile-seat-layout" data-method="GET"
      data-path="api/v1/mobile/seat-layout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mobile-seat-layout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mobile-seat-layout"
                    onclick="tryItOut('GETapi-v1-mobile-seat-layout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mobile-seat-layout"
                    onclick="cancelTryOut('GETapi-v1-mobile-seat-layout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mobile-seat-layout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mobile/seat-layout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mobile-seat-layout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-mobile-seat-layout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="GETapi-v1-mobile-seat-layout"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="class_id"                data-endpoint="GETapi-v1-mobile-seat-layout"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-mobile-reserve">Create a reservation that needs to be paid at the office.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-mobile-reserve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/mobile/reserve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"trip_type\": \"round_trip\",
    \"voyage_id\": 16,
    \"ferry_class_id\": 16,
    \"return_voyage_id\": 16,
    \"return_ferry_class_id\": 16,
    \"passengers\": [
        {
            \"name\": \"n\",
            \"passenger_type_id\": 16,
            \"gender\": \"female\",
            \"nationality_id\": 16,
            \"identity_type_id\": 16,
            \"identity_number\": \"n\",
            \"outbound_seat\": \"gzmiyvdljnikhway\",
            \"return_seat\": \"kcmyuwpwlvqwrsit\",
            \"ferry_class_id\": 16,
            \"seat_number\": \"ngzmiyvdljnikhwa\"
        }
    ],
    \"device_token\": \"b\",
    \"currency\": \"USD\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/reserve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "trip_type": "round_trip",
    "voyage_id": 16,
    "ferry_class_id": 16,
    "return_voyage_id": 16,
    "return_ferry_class_id": 16,
    "passengers": [
        {
            "name": "n",
            "passenger_type_id": 16,
            "gender": "female",
            "nationality_id": 16,
            "identity_type_id": 16,
            "identity_number": "n",
            "outbound_seat": "gzmiyvdljnikhway",
            "return_seat": "kcmyuwpwlvqwrsit",
            "ferry_class_id": 16,
            "seat_number": "ngzmiyvdljnikhwa"
        }
    ],
    "device_token": "b",
    "currency": "USD"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-mobile-reserve">
</span>
<span id="execution-results-POSTapi-v1-mobile-reserve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-mobile-reserve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-mobile-reserve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-mobile-reserve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-mobile-reserve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-mobile-reserve" data-method="POST"
      data-path="api/v1/mobile/reserve"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-mobile-reserve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-mobile-reserve"
                    onclick="tryItOut('POSTapi-v1-mobile-reserve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-mobile-reserve"
                    onclick="cancelTryOut('POSTapi-v1-mobile-reserve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-mobile-reserve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/mobile/reserve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trip_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trip_type"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="round_trip"
               data-component="body">
    <br>
<p>Example: <code>round_trip</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>one_way</code></li> <li><code>round_trip</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="ferry_class_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="return_voyage_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>trip_type</code> is <code>round_trip</code>. Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>return_ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="return_ferry_class_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>This field is required when <code>trip_type</code> is <code>round_trip</code>. Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>passengers</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Must have at least 1 items. Must not have more than 50 items.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.name"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 200 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>passenger_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.passenger_type_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.gender"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="female"
               data-component="body">
    <br>
<p>Example: <code>female</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>male</code></li> <li><code>female</code></li> <li><code>unknown</code></li></ul>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>nationality_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.nationality_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>identity_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.identity_type_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>identity_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.identity_number"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>outbound_seat</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.outbound_seat"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="gzmiyvdljnikhway"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>gzmiyvdljnikhway</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>return_seat</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.return_seat"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="kcmyuwpwlvqwrsit"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>kcmyuwpwlvqwrsit</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="passengers.0.ferry_class_id"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="16"
               data-component="body">
    <br>
<p>backward compatibility. Must match an existing stored value. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>seat_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passengers.0.seat_number"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="ngzmiyvdljnikhwa"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>ngzmiyvdljnikhwa</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_token"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-mobile-reserve"
               value="USD"
               data-component="body">
    <br>
<p>Example: <code>USD</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>TZS</code></li> <li><code>USD</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-mobile-bookings--reference-">Get booking details by reference.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mobile-bookings--reference-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/mobile/bookings/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-mobile-bookings--reference-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mobile-bookings--reference-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mobile-bookings--reference-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mobile-bookings--reference-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mobile-bookings--reference-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mobile-bookings--reference-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mobile-bookings--reference-" data-method="GET"
      data-path="api/v1/mobile/bookings/{reference}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mobile-bookings--reference-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mobile-bookings--reference-"
                    onclick="tryItOut('GETapi-v1-mobile-bookings--reference-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mobile-bookings--reference-"
                    onclick="cancelTryOut('GETapi-v1-mobile-bookings--reference-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mobile-bookings--reference-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mobile/bookings/{reference}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mobile-bookings--reference-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-mobile-bookings--reference-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reference"                data-endpoint="GETapi-v1-mobile-bookings--reference-"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-mobile-bookings--reference--cancel">Cancel an active reservation.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-mobile-bookings--reference--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-mobile-bookings--reference--cancel">
</span>
<span id="execution-results-POSTapi-v1-mobile-bookings--reference--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-mobile-bookings--reference--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-mobile-bookings--reference--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-mobile-bookings--reference--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-mobile-bookings--reference--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-mobile-bookings--reference--cancel" data-method="POST"
      data-path="api/v1/mobile/bookings/{reference}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-mobile-bookings--reference--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-mobile-bookings--reference--cancel"
                    onclick="tryItOut('POSTapi-v1-mobile-bookings--reference--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-mobile-bookings--reference--cancel"
                    onclick="cancelTryOut('POSTapi-v1-mobile-bookings--reference--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-mobile-bookings--reference--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/mobile/bookings/{reference}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-mobile-bookings--reference--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-mobile-bookings--reference--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reference"                data-endpoint="POSTapi-v1-mobile-bookings--reference--cancel"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-mobile-bookings--reference--pay">Initiate payment for a booking using Selcom.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-mobile-bookings--reference--pay">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1/pay" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"phone_number\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1/pay"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "phone_number": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-mobile-bookings--reference--pay">
</span>
<span id="execution-results-POSTapi-v1-mobile-bookings--reference--pay" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-mobile-bookings--reference--pay"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-mobile-bookings--reference--pay"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-mobile-bookings--reference--pay" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-mobile-bookings--reference--pay">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-mobile-bookings--reference--pay" data-method="POST"
      data-path="api/v1/mobile/bookings/{reference}/pay"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-mobile-bookings--reference--pay', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-mobile-bookings--reference--pay"
                    onclick="tryItOut('POSTapi-v1-mobile-bookings--reference--pay');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-mobile-bookings--reference--pay"
                    onclick="cancelTryOut('POSTapi-v1-mobile-bookings--reference--pay');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-mobile-bookings--reference--pay"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/mobile/bookings/{reference}/pay</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-mobile-bookings--reference--pay"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-mobile-bookings--reference--pay"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reference"                data-endpoint="POSTapi-v1-mobile-bookings--reference--pay"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone_number"                data-endpoint="POSTapi-v1-mobile-bookings--reference--pay"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-mobile-bookings--reference--payment-status">Poll payment status.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mobile-bookings--reference--payment-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/mobile/bookings/1/payment-status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/mobile/bookings/1/payment-status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-mobile-bookings--reference--payment-status">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 120
x-ratelimit-remaining: 119
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Booking not found.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mobile-bookings--reference--payment-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mobile-bookings--reference--payment-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mobile-bookings--reference--payment-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mobile-bookings--reference--payment-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mobile-bookings--reference--payment-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mobile-bookings--reference--payment-status" data-method="GET"
      data-path="api/v1/mobile/bookings/{reference}/payment-status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mobile-bookings--reference--payment-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mobile-bookings--reference--payment-status"
                    onclick="tryItOut('GETapi-v1-mobile-bookings--reference--payment-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mobile-bookings--reference--payment-status"
                    onclick="cancelTryOut('GETapi-v1-mobile-bookings--reference--payment-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mobile-bookings--reference--payment-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mobile/bookings/{reference}/payment-status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mobile-bookings--reference--payment-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-mobile-bookings--reference--payment-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>reference</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="reference"                data-endpoint="GETapi-v1-mobile-bookings--reference--payment-status"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-boarding-scan">Process a boarding scan (QR code).</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-boarding-scan">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/boarding/scan" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"qr_data\": \"b\",
    \"voyage_id\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/boarding/scan"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "qr_data": "b",
    "voyage_id": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-boarding-scan">
</span>
<span id="execution-results-POSTapi-v1-boarding-scan" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-boarding-scan"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-boarding-scan"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-boarding-scan" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-boarding-scan">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-boarding-scan" data-method="POST"
      data-path="api/v1/boarding/scan"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-boarding-scan', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-boarding-scan"
                    onclick="tryItOut('POSTapi-v1-boarding-scan');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-boarding-scan"
                    onclick="cancelTryOut('POSTapi-v1-boarding-scan');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-boarding-scan"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/boarding/scan</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-boarding-scan"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-boarding-scan"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>qr_data</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="qr_data"                data-endpoint="POSTapi-v1-boarding-scan"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="voyage_id"                data-endpoint="POSTapi-v1-boarding-scan"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>scanner_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="scanner_id"                data-endpoint="POSTapi-v1-boarding-scan"
               value=""
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>port_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="port_id"                data-endpoint="POSTapi-v1-boarding-scan"
               value=""
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-boarding-manifest--voyageId-">Get boarding manifest for a voyage.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-boarding-manifest--voyageId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/boarding/manifest/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/boarding/manifest/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-boarding-manifest--voyageId-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-boarding-manifest--voyageId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-boarding-manifest--voyageId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-boarding-manifest--voyageId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-boarding-manifest--voyageId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-boarding-manifest--voyageId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-boarding-manifest--voyageId-" data-method="GET"
      data-path="api/v1/boarding/manifest/{voyageId}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-boarding-manifest--voyageId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-boarding-manifest--voyageId-"
                    onclick="tryItOut('GETapi-v1-boarding-manifest--voyageId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-boarding-manifest--voyageId-"
                    onclick="cancelTryOut('GETapi-v1-boarding-manifest--voyageId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-boarding-manifest--voyageId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/boarding/manifest/{voyageId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-boarding-manifest--voyageId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-boarding-manifest--voyageId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>voyageId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="voyageId"                data-endpoint="GETapi-v1-boarding-manifest--voyageId-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-boarding-stats--voyageId-">Get boarding statistics for a voyage.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-boarding-stats--voyageId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/boarding/stats/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/boarding/stats/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-boarding-stats--voyageId-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-boarding-stats--voyageId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-boarding-stats--voyageId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-boarding-stats--voyageId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-boarding-stats--voyageId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-boarding-stats--voyageId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-boarding-stats--voyageId-" data-method="GET"
      data-path="api/v1/boarding/stats/{voyageId}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-boarding-stats--voyageId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-boarding-stats--voyageId-"
                    onclick="tryItOut('GETapi-v1-boarding-stats--voyageId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-boarding-stats--voyageId-"
                    onclick="cancelTryOut('GETapi-v1-boarding-stats--voyageId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-boarding-stats--voyageId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/boarding/stats/{voyageId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-boarding-stats--voyageId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-boarding-stats--voyageId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>voyageId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="voyageId"                data-endpoint="GETapi-v1-boarding-stats--voyageId-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-scanner-login">Authenticate port operator using PIN.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-scanner-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/scanner/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"operator_no\": \"architecto\",
    \"pin\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/scanner/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "operator_no": "architecto",
    "pin": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-scanner-login">
</span>
<span id="execution-results-POSTapi-v1-scanner-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-scanner-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-scanner-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-scanner-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-scanner-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-scanner-login" data-method="POST"
      data-path="api/v1/scanner/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-scanner-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-scanner-login"
                    onclick="tryItOut('POSTapi-v1-scanner-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-scanner-login"
                    onclick="cancelTryOut('POSTapi-v1-scanner-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-scanner-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/scanner/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-scanner-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-scanner-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>operator_no</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="operator_no"                data-endpoint="POSTapi-v1-scanner-login"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pin</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="pin"                data-endpoint="POSTapi-v1-scanner-login"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-scanner-logout">Logout and revoke scanner token.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-scanner-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/scanner/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/scanner/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-scanner-logout">
</span>
<span id="execution-results-POSTapi-v1-scanner-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-scanner-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-scanner-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-scanner-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-scanner-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-scanner-logout" data-method="POST"
      data-path="api/v1/scanner/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-scanner-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-scanner-logout"
                    onclick="tryItOut('POSTapi-v1-scanner-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-scanner-logout"
                    onclick="cancelTryOut('POSTapi-v1-scanner-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-scanner-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/scanner/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-scanner-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-scanner-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-scanner-voyages--voyageId--tickets">Fetch all tickets for a given voyage.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-scanner-voyages--voyageId--tickets">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/scanner/voyages/1/tickets" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/scanner/voyages/1/tickets"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-scanner-voyages--voyageId--tickets">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-scanner-voyages--voyageId--tickets" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-scanner-voyages--voyageId--tickets"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-scanner-voyages--voyageId--tickets"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-scanner-voyages--voyageId--tickets" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-scanner-voyages--voyageId--tickets">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-scanner-voyages--voyageId--tickets" data-method="GET"
      data-path="api/v1/scanner/voyages/{voyageId}/tickets"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-scanner-voyages--voyageId--tickets', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-scanner-voyages--voyageId--tickets"
                    onclick="tryItOut('GETapi-v1-scanner-voyages--voyageId--tickets');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-scanner-voyages--voyageId--tickets"
                    onclick="cancelTryOut('GETapi-v1-scanner-voyages--voyageId--tickets');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-scanner-voyages--voyageId--tickets"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/scanner/voyages/{voyageId}/tickets</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-scanner-voyages--voyageId--tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-scanner-voyages--voyageId--tickets"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>voyageId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyageId"                data-endpoint="GETapi-v1-scanner-voyages--voyageId--tickets"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-scanner-verify">Verify ticket validity without boarding.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-scanner-verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/scanner/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 16,
    \"ticket_number\": \"architecto\",
    \"qr_data\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/scanner/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 16,
    "ticket_number": "architecto",
    "qr_data": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-scanner-verify">
</span>
<span id="execution-results-POSTapi-v1-scanner-verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-scanner-verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-scanner-verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-scanner-verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-scanner-verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-scanner-verify" data-method="POST"
      data-path="api/v1/scanner/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-scanner-verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-scanner-verify"
                    onclick="tryItOut('POSTapi-v1-scanner-verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-scanner-verify"
                    onclick="cancelTryOut('POSTapi-v1-scanner-verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-scanner-verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/scanner/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-scanner-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-scanner-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-scanner-verify"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ticket_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_number"                data-endpoint="POSTapi-v1-scanner-verify"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>qr_data</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="qr_data"                data-endpoint="POSTapi-v1-scanner-verify"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-scanner-board">Board passenger (scan and mark boarded).</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-scanner-board">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/scanner/board" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 16,
    \"ticket_number\": \"architecto\",
    \"qr_data\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/scanner/board"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 16,
    "ticket_number": "architecto",
    "qr_data": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-scanner-board">
</span>
<span id="execution-results-POSTapi-v1-scanner-board" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-scanner-board"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-scanner-board"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-scanner-board" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-scanner-board">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-scanner-board" data-method="POST"
      data-path="api/v1/scanner/board"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-scanner-board', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-scanner-board"
                    onclick="tryItOut('POSTapi-v1-scanner-board');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-scanner-board"
                    onclick="cancelTryOut('POSTapi-v1-scanner-board');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-scanner-board"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/scanner/board</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-scanner-board"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-scanner-board"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-scanner-board"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ticket_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ticket_number"                data-endpoint="POSTapi-v1-scanner-board"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>qr_data</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="qr_data"                data-endpoint="POSTapi-v1-scanner-board"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-symlink-fix">GET api/symlink-fix</h2>

<p>
</p>



<span id="example-requests-GETapi-symlink-fix">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/symlink-fix" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/symlink-fix"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-symlink-fix">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Storage symlink already exists.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-symlink-fix" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-symlink-fix"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-symlink-fix"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-symlink-fix" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-symlink-fix">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-symlink-fix" data-method="GET"
      data-path="api/symlink-fix"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-symlink-fix', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-symlink-fix"
                    onclick="tryItOut('GETapi-symlink-fix');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-symlink-fix"
                    onclick="cancelTryOut('GETapi-symlink-fix');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-symlink-fix"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/symlink-fix</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-symlink-fix"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-symlink-fix"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-notifications">GET api/notifications</h2>

<p>
</p>



<span id="example-requests-GETapi-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/notifications" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/notifications"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-notifications">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
set-cookie: XSRF-TOKEN=eyJpdiI6ImMrbHhzeWpJZ1MxcDM1cEdlSUoyaXc9PSIsInZhbHVlIjoiN0pBdmV3ZlV5SDhFcy82M2xQV2I0R2I4WDhMR2ZGZC91QWdad1dxZ0NQVTQ5VHMzZXVsaklUZnp4K2Ewemk3RlRpSE91S1BzZFYxb0g2T0kwbEhnRE0xZDMwdStUMTFkVThSUU12VFFjY0p1ZXAzVllpaGg0enpBTFk1VVdtQ2UiLCJtYWMiOiJmMjMxYjYwMjlmMWZiMjA0ZmY4NTcyNWJhMDU3MTI1NTRkZjM5YWQ5YzFkMDZhYjVmNTgwZGZjMzkxYWFmZmZhIiwidGFnIjoiIn0%3D; expires=Mon, 24 Aug 2026 10:56:20 GMT; Max-Age=7200; path=/; samesite=lax; victoria-ferries-api-session=eyJpdiI6Iks1SjFkS05ZbWlPVitVL0VDMEFRS1E9PSIsInZhbHVlIjoicEl4NWEwZVlhRGJwMjFGVG9kWHczN0RTVmVOTldDR0FZM1BMc21md0psY1NmR0hqYkwxRE5pRWJkM3RXL09xc05EVjRzWFMxZWEzSDNrZCs3cjJhYjhBa20yd21mQ0VjaTVXdnJZL1ZPcVZWKzM1ZUI4czdoMjJhVzZpaDllMksiLCJtYWMiOiJiNWM4M2ZjY2E2MjI0N2U1MDM5MTdiOTQ1MWM2Mzg3ZmM4MjkyOWY3M2Q0M2ZmYTVmZjljNTkyOTVjMTkyY2EwIiwidGFnIjoiIn0%3D; expires=Mon, 24 Aug 2026 10:56:20 GMT; Max-Age=7200; path=/; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">[]</code>
 </pre>
    </span>
<span id="execution-results-GETapi-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-notifications" data-method="GET"
      data-path="api/notifications"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-notifications"
                    onclick="tryItOut('GETapi-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-notifications"
                    onclick="cancelTryOut('GETapi-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="sales-portal">Sales Portal</h1>

    <p>Booking management endpoints for the sales portal (agents, counter staff, and associates).
All endpoints are data-scoped — agents see only their own bookings and their associates'.</p>

                                <h2 id="sales-portal-GETapi-v1-portal-bookings">List Bookings</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>List bookings scoped to the current user's permissions.
Supports search by reference, passenger name, or phone.</p>

<span id="example-requests-GETapi-v1-portal-bookings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/bookings?search=VF-2026&amp;status=confirmed&amp;date=2026-08-24&amp;per_page=20" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings"
);

const params = {
    "search": "VF-2026",
    "status": "confirmed",
    "date": "2026-08-24",
    "per_page": "20",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-bookings">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;booking_reference&quot;: &quot;VF-20260824-ABC123&quot;,
            &quot;status&quot;: &quot;confirmed&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-bookings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-bookings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-bookings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-bookings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-bookings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-bookings" data-method="GET"
      data-path="api/v1/portal/bookings"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-bookings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-bookings"
                    onclick="tryItOut('GETapi-v1-portal-bookings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-bookings"
                    onclick="cancelTryOut('GETapi-v1-portal-bookings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-bookings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/bookings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-bookings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-bookings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-portal-bookings"
               value="VF-2026"
               data-component="query">
    <br>
<p>Search by reference, passenger name, or phone. Example: <code>VF-2026</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-portal-bookings"
               value="confirmed"
               data-component="query">
    <br>
<p>Filter by booking status. Example: <code>confirmed</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date"                data-endpoint="GETapi-v1-portal-bookings"
               value="2026-08-24"
               data-component="query">
    <br>
<p>Filter by travel date. Example: <code>2026-08-24</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-portal-bookings"
               value="20"
               data-component="query">
    <br>
<p>Results per page. Example: <code>20</code></p>
            </div>
                </form>

                    <h2 id="sales-portal-GETapi-v1-portal-bookings-availability-check">GET api/v1/portal/bookings/availability/check</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-bookings-availability-check">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/bookings/availability/check" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"date\": \"2026-08-24T11:56:20\",
    \"route_id\": 16,
    \"current_class_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/availability/check"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "date": "2026-08-24T11:56:20",
    "route_id": 16,
    "current_class_id": 16
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-bookings-availability-check">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-bookings-availability-check" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-bookings-availability-check"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-bookings-availability-check"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-bookings-availability-check" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-bookings-availability-check">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-bookings-availability-check" data-method="GET"
      data-path="api/v1/portal/bookings/availability/check"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-bookings-availability-check', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-bookings-availability-check"
                    onclick="tryItOut('GETapi-v1-portal-bookings-availability-check');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-bookings-availability-check"
                    onclick="cancelTryOut('GETapi-v1-portal-bookings-availability-check');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-bookings-availability-check"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/bookings/availability/check</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-bookings-availability-check"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-bookings-availability-check"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date"                data-endpoint="GETapi-v1-portal-bookings-availability-check"
               value="2026-08-24T11:56:20"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-08-24T11:56:20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>route_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="route_id"                data-endpoint="GETapi-v1-portal-bookings-availability-check"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="current_class_id"                data-endpoint="GETapi-v1-portal-bookings-availability-check"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings-availability-lock">POST api/v1/portal/bookings/availability/lock</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-bookings-availability-lock">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/availability/lock" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": \"architecto\",
    \"ferry_class_id\": \"architecto\",
    \"quantity\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/availability/lock"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": "architecto",
    "ferry_class_id": "architecto",
    "quantity": 22
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings-availability-lock">
</span>
<span id="execution-results-POSTapi-v1-portal-bookings-availability-lock" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings-availability-lock"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings-availability-lock"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings-availability-lock" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings-availability-lock">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings-availability-lock" data-method="POST"
      data-path="api/v1/portal/bookings/availability/lock"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings-availability-lock', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings-availability-lock"
                    onclick="tryItOut('POSTapi-v1-portal-bookings-availability-lock');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings-availability-lock"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings-availability-lock');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings-availability-lock"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/availability/lock</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings-availability-lock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings-availability-lock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="voyage_id"                data-endpoint="POSTapi-v1-portal-bookings-availability-lock"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ferry_class_id"                data-endpoint="POSTapi-v1-portal-bookings-availability-lock"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="quantity"                data-endpoint="POSTapi-v1-portal-bookings-availability-lock"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 50. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings-availability-unlock">POST api/v1/portal/bookings/availability/unlock</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-bookings-availability-unlock">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/availability/unlock" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/availability/unlock"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings-availability-unlock">
</span>
<span id="execution-results-POSTapi-v1-portal-bookings-availability-unlock" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings-availability-unlock"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings-availability-unlock"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings-availability-unlock" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings-availability-unlock">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings-availability-unlock" data-method="POST"
      data-path="api/v1/portal/bookings/availability/unlock"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings-availability-unlock', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings-availability-unlock"
                    onclick="tryItOut('POSTapi-v1-portal-bookings-availability-unlock');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings-availability-unlock"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings-availability-unlock');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings-availability-unlock"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/availability/unlock</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings-availability-unlock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings-availability-unlock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="sales-portal-GETapi-v1-portal-bookings--id-">Show booking details.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-bookings--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/bookings/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-bookings--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-bookings--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-bookings--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-bookings--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-bookings--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-bookings--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-bookings--id-" data-method="GET"
      data-path="api/v1/portal/bookings/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-bookings--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-bookings--id-"
                    onclick="tryItOut('GETapi-v1-portal-bookings--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-bookings--id-"
                    onclick="cancelTryOut('GETapi-v1-portal-bookings--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-bookings--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/bookings/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-bookings--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-bookings--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-portal-bookings--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--complete">POST api/v1/portal/bookings/{id}/complete</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-bookings--id--complete">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/complete" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/complete"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--complete">
</span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--complete" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--complete"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--complete"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--complete" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--complete">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--complete" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/complete"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--complete', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--complete"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--complete');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--complete"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--complete');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--complete"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/complete</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--complete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--complete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--complete"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sales-portal-PUTapi-v1-portal-bookings--id-">PUT api/v1/portal/bookings/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-portal-bookings--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/v1/portal/bookings/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PUT",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-portal-bookings--id-">
</span>
<span id="execution-results-PUTapi-v1-portal-bookings--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-portal-bookings--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-portal-bookings--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-portal-bookings--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-portal-bookings--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-portal-bookings--id-" data-method="PUT"
      data-path="api/v1/portal/bookings/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-portal-bookings--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-portal-bookings--id-"
                    onclick="tryItOut('PUTapi-v1-portal-bookings--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-portal-bookings--id-"
                    onclick="cancelTryOut('PUTapi-v1-portal-bookings--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-portal-bookings--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/portal/bookings/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-portal-bookings--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-portal-bookings--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1-portal-bookings--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--cancel">Cancel Booking</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Cancel an entire booking. Applies cancellation penalty if configured.
For agent bookings, refund is queued as a pending refund.</p>

<span id="example-requests-POSTapi-v1-portal-bookings--id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"Customer no-show\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "Customer no-show"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--cancel">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Booking cancelled and seats released successfully.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Not found&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Cannot cancel: booking is already cancelled.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--cancel" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/cancel"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--cancel"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--cancel"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--cancel"
               value="42"
               data-component="url">
    <br>
<p>Booking ID or reference. Example: <code>42</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-portal-bookings--id--cancel"
               value="Customer no-show"
               data-component="body">
    <br>
<p>optional Cancellation reason. Example: <code>Customer no-show</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">POST api/v1/portal/bookings/{id}/tickets/batch-reschedule</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/batch-reschedule" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"tickets\": [
        {
            \"ticket_id\": \"architecto\",
            \"voyage_id\": \"architecto\",
            \"ferry_class_id\": \"architecto\",
            \"passenger_name\": \"n\",
            \"lock_id\": 16
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/batch-reschedule"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "tickets": [
        {
            "ticket_id": "architecto",
            "voyage_id": "architecto",
            "ferry_class_id": "architecto",
            "passenger_name": "n",
            "lock_id": 16
        }
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">
</span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/tickets/batch-reschedule"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--tickets-batch-reschedule', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--tickets-batch-reschedule');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--tickets-batch-reschedule');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/tickets/batch-reschedule</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>tickets</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Must have at least 1 items.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ticket_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tickets.0.ticket_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tickets.0.voyage_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tickets.0.ferry_class_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="architecto"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>passenger_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tickets.0.passenger_name"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>lock_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="tickets.0.lock_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets-batch-reschedule"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">Cancel Single Ticket</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Cancel an individual ticket within a booking. The "Last Adult Safeguard"
prevents cancelling the sole remaining adult ticket when minors are present.</p>

<span id="example-requests-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/tickets/105/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"Passenger withdrew\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/tickets/105/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "Passenger withdrew"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Ticket TK-001 cancelled successfully.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Last Adult Safeguard: Cannot cancel the sole remaining Adult ticket when minors are present.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/tickets/{itemId}/cancel"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--tickets--itemId--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--tickets--itemId--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--tickets--itemId--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/tickets/{itemId}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
               value="42"
               data-component="url">
    <br>
<p>Booking ID or reference. Example: <code>42</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>itemId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="itemId"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
               value="105"
               data-component="url">
    <br>
<p>Ticket ID. Example: <code>105</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--cancel"
               value="Passenger withdrew"
               data-component="body">
    <br>
<p>optional Cancellation reason. Example: <code>Passenger withdrew</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">PUT api/v1/portal/bookings/{id}/tickets/{itemId}/passenger</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/1/passenger" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"full_name\": \"b\",
    \"id_number\": \"n\",
    \"gender\": \"female\",
    \"dob\": \"2026-08-24T11:56:20\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/1/passenger"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "full_name": "b",
    "id_number": "n",
    "gender": "female",
    "dob": "2026-08-24T11:56:20"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">
</span>
<span id="execution-results-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger" data-method="PUT"
      data-path="api/v1/portal/bookings/{id}/tickets/{itemId}/passenger"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-portal-bookings--id--tickets--itemId--passenger', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
                    onclick="tryItOut('PUTapi-v1-portal-bookings--id--tickets--itemId--passenger');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
                    onclick="cancelTryOut('PUTapi-v1-portal-bookings--id--tickets--itemId--passenger');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/portal/bookings/{id}/tickets/{itemId}/passenger</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>itemId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="itemId"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>full_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="full_name"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>id_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id_number"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gender"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="female"
               data-component="body">
    <br>
<p>Example: <code>female</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>male</code></li> <li><code>female</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dob</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dob"                data-endpoint="PUTapi-v1-portal-bookings--id--tickets--itemId--passenger"
               value="2026-08-24T11:56:20"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-08-24T11:56:20</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">Reschedule Ticket</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Move a ticket to a different voyage and/or class. May split into a new booking
if the vessel changes. Fare difference is charged or refunded based on config.</p>

<span id="example-requests-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/tickets/105/reschedule" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"voyage_id\": 15,
    \"ferry_class_id\": 2,
    \"passenger_name\": \"Jane Doe\",
    \"lock_id\": 3
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/42/tickets/105/reschedule"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "voyage_id": 15,
    "ferry_class_id": 2,
    "passenger_name": "Jane Doe",
    "lock_id": 3
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Ticket rescheduled successfully.&quot;,
    &quot;data&quot;: {
        &quot;ticket_number&quot;: &quot;TK-002&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Cannot reschedule: ticket is departed.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/tickets/{itemId}/reschedule"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/tickets/{itemId}/reschedule</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="42"
               data-component="url">
    <br>
<p>Booking ID or reference. Example: <code>42</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>itemId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="itemId"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="105"
               data-component="url">
    <br>
<p>Ticket ID. Example: <code>105</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>voyage_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="voyage_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="15"
               data-component="body">
    <br>
<p>Target voyage ID. Example: <code>15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ferry_class_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="ferry_class_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="2"
               data-component="body">
    <br>
<p>Target class ID. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>passenger_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="passenger_name"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="Jane Doe"
               data-component="body">
    <br>
<p>optional Updated passenger name. Example: <code>Jane Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lock_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lock_id"                data-endpoint="POSTapi-v1-portal-bookings--id--tickets--itemId--reschedule"
               value="3"
               data-component="body">
    <br>
<p>optional Seat lock ID to release. Example: <code>3</code></p>
        </div>
        </form>

                    <h2 id="sales-portal-GETapi-v1-portal-bookings--id--print">GET api/v1/portal/bookings/{id}/print</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-bookings--id--print">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/bookings/1/print" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/print"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-bookings--id--print">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-bookings--id--print" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-bookings--id--print"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-bookings--id--print"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-bookings--id--print" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-bookings--id--print">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-bookings--id--print" data-method="GET"
      data-path="api/v1/portal/bookings/{id}/print"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-bookings--id--print', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-bookings--id--print"
                    onclick="tryItOut('GETapi-v1-portal-bookings--id--print');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-bookings--id--print"
                    onclick="cancelTryOut('GETapi-v1-portal-bookings--id--print');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-bookings--id--print"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/bookings/{id}/print</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-bookings--id--print"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-bookings--id--print"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-portal-bookings--id--print"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sales-portal-GETapi-v1-portal-bookings--id--tickets--itemId--print">GET api/v1/portal/bookings/{id}/tickets/{itemId}/print</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-portal-bookings--id--tickets--itemId--print">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/1/print" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/tickets/1/print"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-portal-bookings--id--tickets--itemId--print">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-portal-bookings--id--tickets--itemId--print" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-portal-bookings--id--tickets--itemId--print"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-portal-bookings--id--tickets--itemId--print"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-portal-bookings--id--tickets--itemId--print" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-portal-bookings--id--tickets--itemId--print">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-portal-bookings--id--tickets--itemId--print" data-method="GET"
      data-path="api/v1/portal/bookings/{id}/tickets/{itemId}/print"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-portal-bookings--id--tickets--itemId--print', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-portal-bookings--id--tickets--itemId--print"
                    onclick="tryItOut('GETapi-v1-portal-bookings--id--tickets--itemId--print');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-portal-bookings--id--tickets--itemId--print"
                    onclick="cancelTryOut('GETapi-v1-portal-bookings--id--tickets--itemId--print');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-portal-bookings--id--tickets--itemId--print"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/portal/bookings/{id}/tickets/{itemId}/print</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-portal-bookings--id--tickets--itemId--print"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-portal-bookings--id--tickets--itemId--print"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-portal-bookings--id--tickets--itemId--print"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>itemId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="itemId"                data-endpoint="GETapi-v1-portal-bookings--id--tickets--itemId--print"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sales-portal-POSTapi-v1-portal-bookings--id--email">POST api/v1/portal/bookings/{id}/email</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-portal-bookings--id--email">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/email" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/portal/bookings/1/email"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-portal-bookings--id--email">
</span>
<span id="execution-results-POSTapi-v1-portal-bookings--id--email" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-portal-bookings--id--email"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-portal-bookings--id--email"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-portal-bookings--id--email" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-portal-bookings--id--email">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-portal-bookings--id--email" data-method="POST"
      data-path="api/v1/portal/bookings/{id}/email"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-portal-bookings--id--email', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-portal-bookings--id--email"
                    onclick="tryItOut('POSTapi-v1-portal-bookings--id--email');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-portal-bookings--id--email"
                    onclick="cancelTryOut('POSTapi-v1-portal-bookings--id--email');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-portal-bookings--id--email"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/portal/bookings/{id}/email</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-portal-bookings--id--email"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-portal-bookings--id--email"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-portal-bookings--id--email"
               value="1"
               data-component="url">
    <br>
<p>The ID of the booking. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="webhooks">Webhooks</h1>

    <p>Payment gateway webhook callbacks. These endpoints are called by external
payment providers (e.g., Selcom) to notify the system of payment status changes.
They are NOT meant to be called by frontend clients.</p>

                                <h2 id="webhooks-POSTapi-v1-webhooks-selcom-callback">Selcom Payment Callback</h2>

<p>
</p>

<p>Receives payment confirmation from Selcom gateway. Verifies the webhook signature,
confirms the booking, creates a financial transaction record, and sends push notification.</p>

<span id="example-requests-POSTapi-v1-webhooks-selcom-callback">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/v1/webhooks/selcom/callback" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"order_id\": \"VF-20260824-ABC123\",
    \"payment_status\": \"COMPLETED\",
    \"transid\": \"SEL-TXN-12345\",
    \"amount\": 35000,
    \"msisdn\": \"255712345678\",
    \"channel\": \"TigoTigopesa\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/v1/webhooks/selcom/callback"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "order_id": "VF-20260824-ABC123",
    "payment_status": "COMPLETED",
    "transid": "SEL-TXN-12345",
    "amount": 35000,
    "msisdn": "255712345678",
    "channel": "TigoTigopesa"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-webhooks-selcom-callback">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Booking confirmed&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Missing order_id&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid signature&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-webhooks-selcom-callback" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-webhooks-selcom-callback"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-webhooks-selcom-callback"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-webhooks-selcom-callback" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-webhooks-selcom-callback">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-webhooks-selcom-callback" data-method="POST"
      data-path="api/v1/webhooks/selcom/callback"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-webhooks-selcom-callback', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-webhooks-selcom-callback"
                    onclick="tryItOut('POSTapi-v1-webhooks-selcom-callback');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-webhooks-selcom-callback"
                    onclick="cancelTryOut('POSTapi-v1-webhooks-selcom-callback');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-webhooks-selcom-callback"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/webhooks/selcom/callback</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order_id"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="VF-20260824-ABC123"
               data-component="body">
    <br>
<p>The booking reference. Example: <code>VF-20260824-ABC123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>payment_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="payment_status"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="COMPLETED"
               data-component="body">
    <br>
<p>Payment result. Example: <code>COMPLETED</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>transid</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="transid"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="SEL-TXN-12345"
               data-component="body">
    <br>
<p>optional Selcom transaction ID. Example: <code>SEL-TXN-12345</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="amount"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="35000"
               data-component="body">
    <br>
<p>optional Amount paid. Example: <code>35000</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>msisdn</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="msisdn"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="255712345678"
               data-component="body">
    <br>
<p>optional Payer phone number. Example: <code>255712345678</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="channel"                data-endpoint="POSTapi-v1-webhooks-selcom-callback"
               value="TigoTigopesa"
               data-component="body">
    <br>
<p>optional Payment channel. Example: <code>TigoTigopesa</code></p>
        </div>
        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
