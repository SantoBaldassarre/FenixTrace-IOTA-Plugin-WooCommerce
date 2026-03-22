# FenixTrace for WooCommerce

WordPress/WooCommerce plugin that registers products on the **IOTA L1** blockchain via the FenixTrace Integration Kit.

> Built by [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## How It Works

```
WooCommerce Product → JSON → Integration Kit → IPFS + IOTA L1 → FenixTrace Scanner
```

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

## Links

- [FenixTrace Platform](https://trace.fenixsoftwarelabs.com)
- [Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit)
- [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## License

GPL-2.0-or-later
