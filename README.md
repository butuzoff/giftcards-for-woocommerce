# 🎁 Custom Gift Cards for WooCommerce / Подарункові карти для WooCommerce

[![Release](https://img.shields.io/github/v/release/butuzoff/giftcards-for-woocommerce?style=flat-square&logo=github&color=blue)](https://github.com/butuzoff/giftcards-for-woocommerce/releases)
[![Build Status](https://img.shields.io/github/actions/workflow/status/butuzoff/giftcards-for-woocommerce/release.yml?style=flat-square&logo=github&label=release)](https://github.com/butuzoff/giftcards-for-woocommerce/actions/workflows/release.yml)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple?style=flat-square&logo=woocommerce)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Developer](https://img.shields.io/badge/Developer-Flancer.eu-orange?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K)](https://flancer.eu)

*[English](#english) | [Українська](#українська)*

---

## English

**Custom Gift Cards for WooCommerce** is a comprehensive plugin for creating, managing, and tracking gift cards in WooCommerce. It allows you to create customizable gift card products, track balances with full integration into the WooCommerce ecosystem, and provides secure gift card redemption.

### 📋 Features

- ✅ **Gift Card Creation** - Customizable products with various denominations
- ✅ **Balance Tracking** - In user account dashboard
- ✅ **Partial Usage** - Use gift cards across multiple purchases
- ✅ **Security** - Expiration date validation, data validation, nonce protection
- ✅ **Responsive Design** - Works on all devices
- ✅ **WooCommerce Integration** - Full compatibility with cart and checkout
- ✅ **Email Notifications** - Automatic delivery notifications
- ✅ **GitHub Updates** - Automatic plugin updates

### 🛠️ Technology Stack

![WordPress](https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-96588A?style=for-the-badge&logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white)

### 🚀 Installation

#### Automatic Installation (Recommended)
1. Download the latest release from [GitHub Releases](https://github.com/butuzoff/giftcards-for-woocommerce/releases)
2. Upload the archive to WordPress: **Admin → Plugins → Add New → Upload Plugin**
3. Activate the plugin

#### Manual Installation
1. Clone the repository to `/wp-content/plugins/`
2. Rename the folder to `giftcards-for-woocommerce`
3. Activate the plugin in the admin panel

### ⚙️ Configuration

#### 1. Creating Gift Card Products
1. Go to **Products → Add New**
2. Set product type to **"Gift Card"** (checkbox option)
3. Fill in basic fields:
   - Product name
   - Description
   - Price
   - Image
4. In gift card settings specify:
   - Card denomination
   - Expiration period (days)

#### 2. Email Notifications
The plugin automatically sends email notifications after payment.

#### 3. User Account Setup
Users can view their cards in **My Account → My Gift Cards**

### 📖 Usage

#### For Customers
1. Add gift card to cart
2. Complete checkout as usual
3. Receive email notification after payment
4. Use card for future purchases

#### For Administrators
- Manage gift card balances through WooCommerce orders
- Track gift card usage in order details
- Configure email delivery settings

### 🔧 Technical Requirements

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple?style=flat-square&logo=woocommerce)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-4479A1?style=flat-square&logo=mysql)](https://mysql.com/)

- **WordPress**: 5.0+
- **WooCommerce**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

### 🔒 Security

- All forms protected with nonce tokens
- Input data validation
- Card expiration validation
- Race condition protection
- Operation logging

### 📝 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

```
WordPress Plugin License: GPL v2 or later
```

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 

### 🆘 Support

- **Documentation**: [GitHub Wiki](https://github.com/butuzoff/giftcards-for-woocommerce/wiki)
- **Support**: [Flancer.eu](https://flancer.eu)


---

## Українська

**Подарункові карти для WooCommerce** — це комплексний плагін для створення, управління та відстеження подарункових карт у WooCommerce. Дозволяє створювати налаштовувані товари подарункових карт, відстежувати баланси з повною інтеграцією в екосистему WooCommerce та забезпечує безпечне використання подарункових карт.

### 📋 Можливості

- ✅ **Створення подарункових карт** - Налаштовувані товари з різними номіналами
- ✅ **Відстеження балансу** - В особистому кабінеті користувача
- ✅ **Часткове використання** - Можливість використовувати карту на кілька покупок
- ✅ **Безпека** - Перевірка терміну дії, валідація даних, захист nonce
- ✅ **Адаптивний дизайн** - Працює на всіх пристроях
- ✅ **Інтеграція з WooCommerce** - Повна сумісність з кошиком та оформленням
- ✅ **Email сповіщення** - Автоматичні сповіщення про доставку
- ✅ **GitHub оновлення** - Автоматичні оновлення плагіна

### 🛠️ Стек технологій

![WordPress](https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-96588A?style=for-the-badge&logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white)

### 🚀 Встановлення

#### Автоматичне встановлення (рекомендується)
1. Завантажте останній реліз з [GitHub Releases](https://github.com/butuzoff/giftcards-for-woocommerce/releases)
2. Завантажте архів у WordPress: **Адмін → Плагіни → Додати новий → Завантажити плагін**
3. Активуйте плагін

#### Ручне встановлення
1. Клонуйте репозиторій в `/wp-content/plugins/`
2. Перейменуйте папку на `giftcards-for-woocommerce`
3. Активуйте плагін в адмін-панелі

### ⚙️ Налаштування

#### 1. Створення товарів подарункових карт
1. Перейдіть до **Товари → Додати новий**
2. Встановіть тип товару **"Подарункова карта"** (опція чекбокс)
3. Заповніть основні поля:
   - Назва товару
   - Опис
   - Ціна
   - Зображення
4. В налаштуваннях подарункової карти вкажіть:
   - Номінал карти
   - Термін дії (днів)

#### 2. Email сповіщення
Плагін автоматично відправляє email сповіщення після оплати.

#### 3. Налаштування особистого кабінету
Користувачі можуть переглядати свої карти в **Мій обліковий запис → Мої подарункові карти**

### 📖 Використання

#### Для покупців
1. Додайте подарункову карту в кошик
2. Оформіть замовлення як зазвичай
3. Отримайте email сповіщення після оплати
4. Використовуйте карту для майбутніх покупок

#### Для адміністраторів
- Управління балансами подарункових карт через замовлення WooCommerce
- Відстеження використання карт в деталях замовлення
- Налаштування параметрів email доставки

### 🔧 Технічні вимоги

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple?style=flat-square&logo=woocommerce)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-4479A1?style=flat-square&logo=mysql)](https://mysql.com/)

- **WordPress**: 5.0+
- **WooCommerce**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

### 🔒 Безпека

- Всі форми захищені nonce-токенами
- Валідація всіх вхідних даних
- Перевірка терміну дії карт
- Захист від race conditions
- Логування всіх операцій

### 📝 Ліцензія

Цей плагін ліцензований під [GPL v2 або пізніше](https://www.gnu.org/licenses/gpl-2.0.html).

```
WordPress Plugin License: GPL v2 or later
```

Ця програма є вільним програмним забезпеченням; ви можете поширювати та/або модифікувати її відповідно до умов GNU General Public License, опублікованої Free Software Foundation; або версії 2 Ліцензії, або (на ваш вибір) будь-якої пізнішої версії.

Ця програма поширюється в надії, що вона буде корисною, але БЕЗ БУДЬ-ЯКИХ ГАРАНТІЙ; навіть без неявної гарантії КОМЕРЦІЙНОЇ ПРИДАТНОСТІ або ПРИДАТНОСТІ ДЛЯ КОНКРЕТНОЇ МЕТИ. Дивіться GNU General Public License для отримання додаткової інформації.

### 🆘 Підтримка

- **Документація**: [GitHub Wiki](https://github.com/butuzoff/giftcards-for-woocommerce/wiki)
- **Підтримка**: [Flancer.eu](https://flancer.eu)


### 🔄 Оновлення

Плагін підтримує автоматичні оновлення через GitHub. Нові версії будуть доступні в адмін-панелі WordPress.

---

## 📊 Project Statistics

[![GitHub stars](https://img.shields.io/github/stars/butuzoff/giftcards-for-woocommerce?style=social)](https://github.com/butuzoff/giftcards-for-woocommerce/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/butuzoff/giftcards-for-woocommerce?style=social)](https://github.com/butuzoff/giftcards-for-woocommerce/network/members)
[![GitHub issues](https://img.shields.io/github/issues/butuzoff/giftcards-for-woocommerce?style=flat-square)](https://github.com/butuzoff/giftcards-for-woocommerce/issues)
[![GitHub pull requests](https://img.shields.io/github/issues-pr/butuzoff/giftcards-for-woocommerce?style=flat-square)](https://github.com/butuzoff/giftcards-for-woocommerce/pulls)
[![GitHub downloads](https://img.shields.io/github/downloads/butuzoff/giftcards-for-woocommerce/total?style=flat-square)](https://github.com/butuzoff/giftcards-for-woocommerce/releases)
[![GitHub last commit](https://img.shields.io/github/last-commit/butuzoff/giftcards-for-woocommerce?style=flat-square)](https://github.com/butuzoff/giftcards-for-woocommerce/commits/main)
[![GitHub repo size](https://img.shields.io/github/repo-size/butuzoff/giftcards-for-woocommerce?style=flat-square)](https://github.com/butuzoff/giftcards-for-woocommerce)

**Version**: 1.0.9  
**Last Updated**: 2025  
**Developer**: [Flancer.eu](https://flancer.eu)

## 📋 Changelog

### 1.0.9 (2025-01-XX)
* **Security Enhancement**: Added comprehensive rate limiting system to prevent brute force attacks
* **Security Enhancement**: Implemented suspicious activity detection and automatic card blocking
* **Bug Fix**: Fixed double balance deduction issue in checkout process
* **Security Enhancement**: Added IP-based temporary blocking for abusive behavior
* **Security Enhancement**: Enhanced logging for all gift card operations
* **Security Enhancement**: Added failed attempts tracking per gift card
* **Code Improvement**: Centralized security functions in dedicated file
* **Code Improvement**: Removed duplicate functions and improved code organization

### 1.0.8 (2025-01-XX)
* Enhanced security with comprehensive nonce validation
* Improved gift card expiration management
* Added race condition protection for balance deductions
* Mobile-responsive design improvements
* Detailed operation logging
* WooCommerce 5.0+ compatibility improvements 