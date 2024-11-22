# Raty Alior Bank
## Magento 2 Integration

> [!IMPORTANT]  
> The extension published in this repository is solely a copy of the files
> from the [official source provided by Alior Bank](https://www.aliorbank.pl/klienci-indywidualni/kredyty-i-pozyczki/kredyty-ratalne/informacje-dla-partnerow-handlowych.html).

### Installation

You should **never** install or upgrade extensions directly in the Production environment.

1. Install extension package using Composer:

       composer require lbajsarowicz/magento2-alior-bank-raty

2. Install extension to Magento

       bin/magento setup:upgrade

3. Commit changes to `composer.json`, `composer.lock` and `config.php` to your Version Control system.

### Why?

For many years, Alior Bank did not provide a way to get notifications about the new versions of extension being available for upgrade. Additionally, lack of Best Practices in the module caused countless issues with upgrades and compatibility.

### How?

The Github Action behind this repository is checking daily for the new versions of extension and:

1. Automatically extracts the ZIP file from Alior Bank website,
2. Fetches the `etc/module.xml` version tag and creates a commit,
3. Tags commit using version number from (2),
4. Pushes changes to the repository.

As a result, Store Owners would receive notification from their [Dependabot](https://github.com/dependabot) or [Private Packagist](https://packagist.com/home) about the new version being released.

### Owners / Authors

This extension belongs and is officially maintained by [Alior Bank](https://www.aliorbank.pl/klienci-indywidualni/kredyty-i-pozyczki/kredyty-ratalne/informacje-dla-partnerow-handlowych.html#zapraszamy-do-kontaktu)

### Maintainer

This Repository and automation was introduced by [Łukasz Bajsarowicz](https://lbajsarowicz.me).
- Questions and requests: [lukasz@lbajsarowicz.me](mailto:lukasz@lbajsarowicz.me)
