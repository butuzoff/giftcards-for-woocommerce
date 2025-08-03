# 🚀 Release Guide for Gift Cards Plugin

## Автоматическое создание релизов

Плагин использует GitHub Actions для автоматического создания релизов при создании тегов версий.

### Как создать новый релиз:

1. **Обновите версию в коде:**
   ```php
   // В файле custom-giftcards-for-woocommerce.php
   * Version: 1.0.9
   ```

2. **Закоммитьте изменения:**
   ```bash
   git add .
   git commit -m "feat: Update to version 1.0.9"
   git push origin main
   ```

3. **Создайте тег:**
   ```bash
   git tag v1.0.9
   git push origin v1.0.9
   ```

4. **GitHub Actions автоматически:**
   - Проверит структуру плагина
   - Извлечет версию из заголовка файла
   - Создаст архив `giftcards-for-woocommerce-1.0.9.zip`
   - Создаст релиз на GitHub
   - Загрузит архив в релиз

### Структура архива

Архив будет содержать:
```
giftcards-for-woocommerce/
├── custom-giftcards-for-woocommerce.php
├── readme.txt
└── includes/
    ├── account-giftcards.php
    ├── admin-product-fields.php
    ├── cart-giftcard-form.php
    ├── checkout-filters.php
    ├── checkout-giftcard-payment.php
    ├── generate-giftcards.php
    ├── github-updater.php
    ├── post-types.php
    ├── shipping-email.php
    └── shortcodes.php
```

### Что исключается из архива

- Файлы разработки (.git, .vscode, .idea, .cursor)
- Документация разработчика (README.md, RELEASE_*)
- GitHub Actions (.github/)
- Системные файлы (.DS_Store, logs)
- Зависимости (node_modules, vendor)

### Проверка релиза

После создания релиза:
1. Скачайте архив с GitHub Releases
2. Распакуйте и проверьте структуру
3. Загрузите в тестовую установку WordPress
4. Убедитесь что плагин активируется корректно

### Устранение проблем

**Если релиз не создается:**
- Проверьте что тег начинается с `v` (например: `v1.0.9`)
- Убедитесь что версия в файле плагина совпадает с тегом
- Проверьте логи GitHub Actions во вкладке Actions

**Если структура архива неправильная:**
- Проверьте что все нужные файлы существуют в репозитории
- Убедитесь что нет конфликтов в .gitignore
