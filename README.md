# OMPAY Integration Example

This repository provides a working example of integrating OMPAY payment gateway in PHP.

## Getting Started

1. **Obtain OMPAY Credentials**
    - Log in to your OMPAY Merchant Dashboard.
    - Navigate to the API section to find your **Client ID**, **Client Secret**, and other required details.

2. **Configure Credentials**
    - Open the `ompay.php` file in this repository.
    - Replace the placeholder values with your actual OMPAY credentials:
      ```php
      define("OMPAY_CLIENT_ID", "YOUR_CLIENT_ID");
      define("OMPAY_CLIENT_SECRET", "YOUR_CLIENT_SECRET");
      define("OMPAY_CARD_ENCRYPTION_KEY", "YOUR_CARD_ENCRYPTION_KEY");//NOT REQUIRED FOR CHECKOUT MODE
      // Add other required details here
      ```

3. **Run the Example**
    - Place the files in your web server directory (e.g., `/var/www/`).
    - Access the example via your browser to test the integration.

## Notes

- Never share your Client Secret or other sensitive credentials publicly.

## License

This project is for demonstration purposes only.