# Midtrans Snap QRIS Sandbox

The cashier supports cash, manual QRIS, and Midtrans Snap QRIS. Cash and manual QRIS retain their existing behavior. Midtrans payments remain pending until a signed notification confirms settlement.

## Configure credentials

Copy the Sandbox Server Key and Client Key from the Midtrans dashboard into the project root `.env`. Keep both keys private, do not commit `.env`, and leave production mode disabled:

```dotenv
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=<Sandbox Client Key copied from the dashboard>
MIDTRANS_IS_PRODUCTION=false
```

In the Midtrans dashboard, make sure the environment selector is on **Sandbox**. Create a dedicated Server Key with **Write** permission for **Snap Checkout**; leave other products and capabilities set to None. A read-only Server Key cannot create Snap transactions. Copy the Client Key shown in that same Sandbox environment; rely on the dashboard environment selector rather than guessing from its prefix.
Then run `php artisan config:clear` and `php artisan migrate`. `composer install` installs the official `midtrans/midtrans-php` SDK. The client key is only delivered to the cashier's authenticated receipt page; the server key is used only server-side.

## Test a Sandbox payment

Run the Laravel app locally, choose **QRIS Midtrans** at checkout, and click **Tampilkan QR pembayaran** on the receipt. Complete the payment in the Midtrans Sandbox simulator. The order remains pending until the webhook updates it.

Set the Midtrans Sandbox Payment Notification URL to `https://YOUR-TEMPORARY-TUNNEL/webhooks/midtrans`. The webhook route is public by design, verifies Midtrans' SHA-512 signature and order amount, and handles repeated notifications idempotently. For local testing only, use a short-lived HTTPS tunnel such as Cloudflare Tunnel or ngrok pointed at the local Laravel port. Keep the tunnel URL private, enable it only while testing, then stop the tunnel and remove the URL from the Midtrans dashboard. Do not expose the Laravel app broadly or use a production credential.

Pending payment stock is reserved at checkout and restored if Midtrans reports expiration, cancellation, or denial. A paid, expired, cancelled, or denied state is terminal for webhook replay handling.


