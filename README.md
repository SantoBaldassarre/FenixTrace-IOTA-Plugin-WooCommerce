# FenixTrace for WooCommerce

WordPress/WooCommerce plugin that sends your product data to FenixTrace for product traceability and EU-compliance readiness (EUDR, Digital Product Passport). FenixTrace handles notarization, tamper-proof evidence and compliance automatically, server-side — so you get origin proof and anti-counterfeiting protection without leaving WooCommerce.

> Built by [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## How It Works

```
WooCommerce Product → JSON → Integration Kit → FenixTrace → FenixTrace Scanner
```

The plugin's only job is to send product data to FenixTrace. Notarization, evidence storage and compliance are handled automatically on the server side — records are notarized and tamper-proof, so origin and authenticity can be verified by scanning.

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+
- [FenixTrace Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit) running

## Installation

1. Download or clone this repository
2. Copy the folder to `wp-content/plugins/fenixtrace-woocommerce/`
3. Activate from **Plugins** in WordPress admin
4. Go to **WooCommerce → FenixTrace** to configure

## Configuration

| Setting | Description |
|---|---|
| Integration Kit URL | Where the Kit is running (default: `http://localhost:3005`) |
| Upload Directory | Optional path to Kit's `uploads/` folder |
| Auto-sync on Publish | Automatically sync new products |
| Product Template | Category template (agro, pharma, fashion, etc.) |

## Usage

### Single Product
Edit any product → sidebar **"FenixTrace Blockchain"** → click **"Send to FenixTrace"**

### Bulk Sync
Products list → select products → **Bulk Actions** → **"Send to FenixTrace"**

### Auto-Sync
Enable in settings — products are automatically synced when published.

## Other Plugins

| Plugin | Platform | Repository |
|---|---|---|
| **FenixTrace for Odoo** | Odoo 16/17 | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-Odoo) |
| **FenixTrace for PrestaShop** | PrestaShop 1.7/8.x | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-PrestaShop) |

## Links

- [FenixTrace Platform](https://fenixtrace.com)
- [FenixTrace Integration Docs](https://fenixtrace.com/docs/integration-gateway)
- [Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit)
- [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## License

GPL-2.0-or-later
