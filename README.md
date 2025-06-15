# 🔐 Eloquent Encryptable

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Encryption-Hill_Cipher-blue?style=for-the-badge" alt="Hill Cipher">
</p>

<p align="center">
  <strong>A powerful Laravel package for encrypting Eloquent model attributes using the Hill Cipher algorithm with multi-language support.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/github/license/hamoi1/eloquent-encryptable?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/version-0.1.4-brightgreen?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/Laravel-11%2B-orange?style=flat-square" alt="Laravel Version">
</p>

---

## ✨ Features

- 🔒 **Hill Cipher Encryption**: Advanced matrix-based encryption algorithm
- 🌐 **Multi-language Support**: English, Kurdish, and Arabic character sets
- 🚀 **Automatic Encryption/Decryption**: Seamless model attribute handling
- ✅ **Validation Rules**: Built-in unique and exists validation for encrypted fields
- 🎨 **Blade Directives**: Easy encryption/decryption in views
- 🔄 **Key Rotation**: Re-encrypt data with new keys using console commands
- ⚡ **Performance Optimized**: Chunked processing for large datasets

---

## 📋 Table of Contents

- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
  - [Basic Usage](#basic-usage)
  - [Validation Rules](#validation-rules)
  - [Blade Directives](#blade-directives)
  - [Console Commands](#console-commands)
- [Advanced Usage](#-advanced-usage)
- [API Reference](#-api-reference)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require hamoi1/eloquent-encryptable
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Hamoi1\EloquentEncryptAble\EloquentEncryptAbleServiceProvider" --tag="config"
```

---

## ⚙️ Configuration

After publishing, configure your encryption settings in `config/eloquent-encryptable.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hill Cipher Key Matrix
    |--------------------------------------------------------------------------
    |
    | The key matrix for the Hill cipher encryption. Must be a square matrix
    | (2x2 or 3x3) and invertible. The matrix should be provided as a JSON string.
    |
    */
    'key' => '[[3,2],[5,7]]', // 2x2 matrix example
    
    /*
    |--------------------------------------------------------------------------
    | Previous Key Matrix
    |--------------------------------------------------------------------------
    |
    | Used for key rotation. Store your previous key here when updating
    | the main key to allow re-encryption of existing data.
    |
    */
    'previous_key' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Models to Re-encrypt
    |--------------------------------------------------------------------------
    |
    | List of model classes that should be processed during key rotation.
    |
    */
    'models' => [
        // App\Models\User::class,
        // App\Models\Customer::class,
    ],
];
```

### 🔑 Key Matrix Requirements

- **Size**: 2x2 or 3x3 square matrix
- **Invertible**: Must have a determinant that is coprime with the alphabet size
- **Format**: JSON string representation of the matrix

**Example valid matrices:**
```php
// 2x2 matrix
'key' => '[[3,2],[5,7]]'

// 3x3 matrix  
'key' => '[[6,24,1],[13,16,10],[20,17,15]]'
```

---

## 📖 Usage

### Basic Usage

Add the `EncryptAble` trait to your Eloquent model and define the `$encryptAble` property:

```php
<?php

namespace App\Models;

use Hamoi1\EloquentEncryptAble\Traits\EncryptAble;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use EncryptAble;

    protected $fillable = [
        'name', 'email', 'phone', 'address'
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encryptAble = [
        'phone', 'address'
    ];
}
```

Now your specified attributes will be automatically encrypted when saving and decrypted when retrieving:

```php
// Create a new user - phone and address will be encrypted automatically
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    'address' => '123 Main Street'
]);

// Retrieve user - phone and address will be decrypted automatically
$user = User::find(1);
echo $user->phone; // +1234567890 (decrypted)
echo $user->address; // 123 Main Street (decrypted)
```

### Validation Rules

The package provides custom validation rules for encrypted fields:

#### Unique Rule

Ensure encrypted field values are unique:

```php
use Hamoi1\EloquentEncryptAble\Rules\EncryptAbleUniqueRule;

public function rules()
{
    return [
        'phone' => [
            'required',
            new EncryptAbleUniqueRule('users', 'phone')
        ],
        
        // For updates, exclude current record
        'email' => [
            'required',
            new EncryptAbleUniqueRule('users', 'email', [
                'column' => 'id',
                'value' => $this->user->id
            ])
        ],
    ];
}
```

#### Exists Rule

Validate that encrypted field value exists:

```php
use Hamoi1\EloquentEncryptAble\Rules\EncryptAbleExistRule;

public function rules()
{
    return [
        'parent_phone' => [
            'required',
            new EncryptAbleExistRule('users', 'phone')
        ],
    ];
}
```

### Blade Directives

Use convenient Blade directives in your views:

```blade
{{-- Decrypt a value --}}
@decrypt($user->phone)

{{-- Decrypt with default value --}}
@decrypt($user->phone, 'N/A')

{{-- Encrypt a value --}}
@encrypt('sensitive data')
```

**Example in a Blade template:**
```blade
<div class="user-info">
    <p><strong>Name:</strong> {{ $user->name }}</p>
    <p><strong>Phone:</strong> @decrypt($user->phone, 'Not provided')</p>
    <p><strong>Address:</strong> @decrypt($user->address)</p>
</div>
```

### Console Commands

#### Re-encrypt Data

When rotating encryption keys, use the console command to re-encrypt existing data:

```bash
php artisan eloquent-encryptable:re-encrypt
```

This command will:
1. Load models from the configuration
2. Decrypt data using the previous key
3. Re-encrypt using the new key
4. Show progress bars and timing information
5. Process data in chunks for memory efficiency

---

## 🔧 Advanced Usage

### Manual Encryption/Decryption

Access the encryption service directly:

```php
use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;

$service = app(EloquentEncryptAbleService::class);

// Encrypt a string
$encrypted = $service->encrypt('sensitive data');

// Decrypt a string
$decrypted = $service->decrypt($encrypted);

// Decrypt using previous key (for key rotation)
$decrypted = $service->decrypt($encrypted, true);
```

### Batch Operations

Process multiple model attributes:

```php
$data = [
    'phone' => '+1234567890',
    'address' => '123 Main Street',
    'ssn' => '123-45-6789'
];

$fields = ['phone', 'address', 'ssn'];

// Encrypt multiple fields
$encrypted = $service->encryptModelData($data, $fields);

// Decrypt multiple fields
$decrypted = $service->decryptModelData($encrypted, $fields);

// Re-encrypt with new key
$reEncrypted = $service->reEncryptModelData($encrypted, $fields);
```

### Custom Key Matrix Generation

Generate a random invertible key matrix:

```php
// This will throw an exception with a suggested matrix if current key is invalid
try {
    $service = app(EloquentEncryptAbleService::class);
    $service->encrypt('test');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // Contains the suggested matrix
}
```

---

## 📚 API Reference

### EncryptAble Trait

| Method | Description |
|--------|-------------|
| `bootEncryptAble()` | Automatically encrypts/decrypts model attributes |

### EloquentEncryptAbleService

| Method | Parameters | Description |
|--------|------------|-------------|
| `encrypt(string $word)` | `$word` - Text to encrypt | Encrypts a string using Hill cipher |
| `decrypt(string $encrypted, bool $previousKey = false)` | `$encrypted` - Encrypted text<br>`$previousKey` - Use previous key | Decrypts a string |
| `encryptModelData(array $data, array $fields)` | `$data` - Model data<br>`$fields` - Fields to encrypt | Encrypts specified model fields |
| `decryptModelData(array $data, array $fields)` | `$data` - Model data<br>`$fields` - Fields to decrypt | Decrypts specified model fields |
| `reEncryptModelData(array $data, array $fields)` | `$data` - Model data<br>`$fields` - Fields to re-encrypt | Re-encrypts using new key |

### Validation Rules

| Rule | Constructor Parameters | Description |
|------|----------------------|-------------|
| `EncryptAbleUniqueRule` | `$table, $column, $except = []` | Validates uniqueness of encrypted field |
| `EncryptAbleExistRule` | `$table, $column, $except = []` | Validates existence of encrypted field |

---

## 🔍 Troubleshooting

### Common Issues

**1. Invalid Key Matrix Error**
```
InvalidArgumentException: Invalid Hill cipher key matrix in .env file.
```
**Solution:** Ensure your key matrix is:
- A valid JSON string
- Square (2x2 or 3x3)
- Invertible (determinant coprime with alphabet size)

**2. Memory Issues with Large Datasets**
```
Fatal error: Allowed memory size exhausted
```
**Solution:** The re-encrypt command processes data in chunks of 100. For very large datasets, consider:
- Increasing PHP memory limit
- Processing models individually
- Running during off-peak hours

**3. Character Encoding Issues**
```
Encrypted text appears garbled
```
**Solution:** Ensure your database columns support UTF-8 encoding:
```sql
ALTER TABLE users MODIFY COLUMN address TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Debug Mode

Enable debug logging by adding to your model:

```php
protected static function bootEncryptAble()
{
    parent::bootEncryptAble();
    
    if (config('app.debug')) {
        \Log::info('Encrypting model: ' . static::class);
    }
}
```

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow the development setup below
4. Make your changes and add tests
5. Ensure all tests pass and code follows PSR-12 standards
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/hamoi1/eloquent-encryptable.git
   cd eloquent-encryptable
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up testing environment**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   ```

4. **Create test configuration**
   Create `config/eloquent-encryptable.php` for testing:
   ```php
   <?php
   return [
       'key' => '[[3,2],[5,7]]',
       'previous_key' => '[[2,3],[1,4]]',
       'models' => [
           'App\\Models\\TestUser',
       ],
   ];
   ```

### Project Structure

```
eloquent-encryptable/
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       └── ReEncryptDataCommand.php
│   ├── Rules/
│   │   ├── EncryptAbleExistRule.php
│   │   └── EncryptAbleUniqueRule.php
│   ├── Services/
│   │   └── EloquentEncryptAbleService.php
│   ├── Traits/
│   │   └── EncryptAble.php
│   └── EloquentEncryptAbleServiceProvider.php
├── config/
│   └── eloquent-encryptable.php
├── composer.json
└── README.md
```

### Adding New Features

When adding new features:

1. **Create tests first** (TDD approach)
2. **Follow existing patterns** in the codebase
3. **Update documentation** in README.md
4. **Add PHPDoc comments** for all public methods
5. **Consider backward compatibility**
---

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).