# Cashfree PG REST API Integration (v2025-01-01)

This project demonstrates a secure and modern integration of the **Cashfree Payment Gateway** using direct REST API calls in Laravel, bypassing the need for heavy SDKs.

## Features

- **Direct REST API Integration**: Uses Laravel `Http` client for all interactions (v2025-01-01).
- **Secure Webhooks**: Implements SHA256 HMAC signature verification for incoming webhooks.
- **Order Tracking**: Local database integration to track order lifecycles (Pending, Paid, Failed).
- **Auto-Sync**: Verification endpoint to sync status directly from Cashfree upon customer redirection.
- **Modern UI**: Clean, responsive Blade templates with Tailwind CSS.

## Getting Started

1. **Clone and Install**:
   ```bash
   composer install
   npm install && npm run dev
   ```

2. **Configure Credentials**:
   Add your Cashfree credentials to `.env`:
   ```env
   CASHFREE_CLIENT_ID=your_client_id
   CASHFREE_CLIENT_SECRET=your_client_secret
   CASHFREE_ENV=sandbox # or production
   ```

3. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

4. **Start the Server**:
   ```bash
   php artisan serve
   ```

## API Testing

A Postman collection is included in the root: `Cashfree_PG_v2025.postman_collection.json`.

### Endpoints:
- `POST /api/payments/create-order`: Initiate a payment.
- `GET /api/payments/{order_id}`: Check local payment status.
- `GET /api/payments/verify/{order_id}`: Fetch latest status from Cashfree and sync local DB.
- `POST /api/payments/webhook`: Handle asynchronous payment notifications.

## Technologies
- Laravel 12.x
- Tailwind CSS
- Cashfree PG API v2025-01-01
