# Eloquent Encryptable 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hamoi1/eloquent-encryptable.svg?style=flat-square)](https://packagist.org/packages/hamoi1/eloquent-encryptable)
[![Total Downloads](https://img.shields.io/packagist/dt/hamoi1/eloquent-encryptable.svg?style=flat-square)](https://packagist.org/packages/hamoi1/eloquent-encryptable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

This package allows you to encrypt and decrypt model fields using the Hill Cipher algorithm in your Laravel application.

## Table of Contents
- [Features](#features)
- [Matrix Key Support](#matrix-key-support)
- [Language Support](#language-support)
- [Installation](#installation)
  - [1. Install the package](#1-install-the-package)
  - [2. Publish the configuration file](#2-publish-the-configuration-file)
  - [3. Configure the key matrix](#3-configure-the-key-matrix)
  - [4. Use the Hill Cipher service](#4-use-the-hill-cipher-service)
  - [5. Encrypt and decrypt model fields](#5-encrypt-and-decrypt-model-fields)
  - [6. Re-encrypt model data](#6-re-encrypt-model-data)

## Features
- Encrypts and decrypts model fields using the Hill Cipher algorithm.
- Supports re-encryption of model data with a new key matrix.
- Handles uppercase and lowercase letters, spaces, and numbers.
- encrypt and decrypt text using the Hill Cipher algorithm.
- Supports 2x2 and 3x3 key matrices.

## Matrix Key Support

| Matrix Size    | Status       | Notes                  |
|--------------------|----------------------|------------------------|
| 2x2            | ✅ Supported | Fully functional.      |
| 3x3            | ✅ Supported | Fully functional.      |

# Language Support
- [✅] English
- [✅] Kurdish (Sorani) 
- [✅] Arabic


## Installation
### 1. Install the package

You can install the `eloquent-encryptable` package via composer:
```bash
composer require hamoi1/eloquent-encryptable
```

### 2. Publish the configuration file
you can publish the configuration file to change the key matrix for encryption and decryption, and assign models to re-encrypt by running the following command:
```bash
php artisan vendor:publish  --provider="Hamoi1\\EloquentEncryptAble\\EloquentEncryptAbleServiceProvider" --tag="config"
```

### 3. Configure the key matrix
in `.env` file you can configure the key matrix for encryption and decryption, by adding the following lines:

```env
# for 2x2 matrix
ELOQUENT_ENCRYPTABLE_KEY= "[[4, 7], [3, 10]]"
ELOQUENT_ENCRYPTABLE_PREVIOUS_KEY= "[[14, 17], [13, 20]]"

# for 3x3 matrix
ELOQUENT_ENCRYPTABLE_KEY= "[[1, 11,6], [21, 20,15] ,[2, 20, 9]]"
ELOQUENT_ENCRYPTABLE_PREVIOUS_KEY= "[[13,5,14],[7,10,1],[4,3,16]]"
```
now the key matrix should be a 2x2 matrix, and the previous key matrix is used to re-encrypt model data with a new key matrix.

### 4. Use for text encryption and decryption
You can use the `EloquentEncryptAbleService` service to encrypt and decrypt text using the Hill Cipher algorithm.
```php
use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;

$cipher = new EloquentEncryptAbleService();
$encrypted = $cipher->encrypt('Hello, World!');
$decrypted = $cipher->decrypt($encrypted);
```

output of the above code will be:

```php
$encrypted ='"Ejrno, Wtenl!";
$decrypted = 'Hello, World!';
```

### 5. Encrypt and decrypt model fields
You can encrypt and decrypt model fields by using the `EncryptAble` trait in your model class, and specify the fields that you want to encrypt in the `$encryptAble` property.
```php
use Illuminate\Database\Eloquent\Model;
use Hamoi1\EloquentEncryptAble\Traits\EncryptAble;

class User extends Model
{
    use EncryptAble;

    public $encryptAble = ['name', 'email'];
}
```
now the `name` and `email` fields will be encrypted and decrypted automatically,
when you save and retrieve , like the following example:
```php
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@gmail.com';
$user->save();

$user = User::find(1);
$user->name; // John Doe
$user->email; // john@gmail.com
```
the `name` and `email` fields will be encrypted in the database, and decrypted when you retrieve them.

### 6. Re-encrypt model data
You can re-encrypt model data with a new key matrix , but you should specify the previous key matrix in the `.env` file.

```env
# for 2x2 key matrix
ELOQUENT_ENCRYPTABLE_KEY= "[[4, 7], [3, 10]]"

# for 3x3 key matrix
ELOQUENT_ENCRYPTABLE_KEY= "[[1, 11,6], [21, 20,15] ,[2, 20, 9]]"
```

and added models that you want to re-encrypt in the `config/eloquent-encryptable.php` file:
```php
'models' => [
    User::class,
    Category::class
],
```
then you can run the following command to re-encrypt model data:
```bash
php artisan eloquent-encryptable:re-encrypt
```
This command will re-encrypt all model fields that are encrypted with the previous key matrix will be re-encrypted with the new key matrix.
## Security

If you discover any security-related issues, please email [ihama9728@gmail.com](mailto:ihama9728@gmail.com) instead of using the issue tracker.


## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.