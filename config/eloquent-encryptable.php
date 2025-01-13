<?php

return [
    /**
     *  The key used for encryption and decryption
     *  The key must be a 2x2 matrix
     *  Example: [[21, 4], [2, 5]]
     *  */
    'key' => env('ELOQUENT_ENCRYPTABLE_KEY', '[[21, 4], [2, 5]]'),
    /**
     * the previous key used for encryption and decryption the last encrypted data in database
     * this key used for re encrypt the data with the new key
     */
    'previous_key' => env('ELOQUENT_ENCRYPTABLE_PREVIOUS_KEY', '[[21, 4], [2, 5]]'),

    /**
     * The models to be encrypted and decrypted
     * The models must have the EncryptAble trait
     * Example: [User::class]
     */
    'models' => [
        // User::class,
        // Category::class
    ],
];
