<?php

namespace Hamoi1\EloquentEncryptAble\Services;

use InvalidArgumentException;

class EloquentEncryptAbleService
{
    /**
     * The alphabet used for the Hill cipher.
     *
     * @var string
     */
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const SPECIAL_CHARACTERS = "!@#$%^&*()_+-=[]{}|;':,.<>?/`~";

    /**
     * Returns the size of the alphabet.
     */
    private function getSize(): int
    {
        return strlen(self::ALPHABET);
    }

    /**
     * Converts a letter to number
     *
     * @param  string  $letter  The letter to convert.
     * @return int The numerical representation of the letter.
     */
    private function charToNumber(string $letter): int
    {
        return strpos(self::ALPHABET, $letter);
    }

    /**
     * Converts a number to a letter.
     *
     * @param  int  $number  The number to convert.
     * @return string The corresponding letter.
     */
    private function numberToChar(int $number): string
    {
        return self::ALPHABET[$number];
    }

    /**
     * Calculates the determinant of a 2x2 or 3x3 matrix.
     *
     * @param  array  $keyMatrix  The key matrix.
     * @return int The determinant of the matrix.
     */
    private function determinantMatrix(array $keyMatrix): int
    {
        return match (count($keyMatrix)) {
            2 => $keyMatrix[0][0] * $keyMatrix[1][1] - $keyMatrix[0][1] * $keyMatrix[1][0],
            3 => (
                $keyMatrix[0][0] * ($keyMatrix[1][1] * $keyMatrix[2][2] - $keyMatrix[1][2] * $keyMatrix[2][1]) -
                $keyMatrix[0][1] * ($keyMatrix[1][0] * $keyMatrix[2][2] - $keyMatrix[1][2] * $keyMatrix[2][0]) +
                $keyMatrix[0][2] * ($keyMatrix[1][0] * $keyMatrix[2][1] - $keyMatrix[1][1] * $keyMatrix[2][0])
            )
        };
    }

    /**
     * Calculates the modular inverse of a 2x2 or 3x3 matrix.
     *
     * @param  array  $keyMatrix  The key matrix.
     * @return array The inverse of the matrix.
     */
    private function inverseMatrix(array $keyMatrix): array
    {
        $determinant = $this->determinantMatrix($keyMatrix) % self::getSize();

        if ($determinant < 0) {
            $determinant += self::getSize();
        }

        $scalar = 0;

        // Find modular inverse of determinant
        for ($i = 0; $i < self::getSize(); $i++) {
            if (($i * $determinant) % self::getSize() == 1) {
                $scalar = $i;
                break;
            }
        }

        if (
            count($keyMatrix) == 2
        ) {
            return [
                [($keyMatrix[1][1] * $scalar) % (self::getSize()), ((self::getSize()) - ($keyMatrix[0][1] * $scalar) % (self::getSize())) % (self::getSize())],
                [((self::getSize()) - ($keyMatrix[1][0] * $scalar) % (self::getSize())) % (self::getSize()), ($keyMatrix[0][0] * $scalar) % (self::getSize())],
            ];
        } elseif (count($keyMatrix) == 3) {
            $a = $keyMatrix[0][0];
            $b = $keyMatrix[0][1];
            $c = $keyMatrix[0][2];
            $d = $keyMatrix[1][0];
            $e = $keyMatrix[1][1];
            $f = $keyMatrix[1][2];
            $g = $keyMatrix[2][0];
            $h = $keyMatrix[2][1];
            $i = $keyMatrix[2][2];
            /*
                [a b c]
                [d e f]
                [g h i]
            */
            $adj = [
                [$e * $i - $f * $h, $c * $h - $b * $i, $b * $f - $c * $e],
                [$f * $g - $d * $i, $a * $i - $c * $g, $c * $d - $a * $f],
                [$d * $h - $e * $g, $b * $g - $a * $h, $a * $e - $b * $d],
            ];

            return $inverse = array_map(function ($row) use ($scalar) {
                return array_map(function ($value) use ($scalar) {
                    return ($value * $scalar) % self::getSize();
                }, $row);
            }, $adj);
        } else {
            throw new InvalidArgumentException('Invalid matrix size.');
        }
    }

    /**
     * Performs matrix multiplication with a 2x2 or 3x3 key matrix and a vector.
     *
     * @param  array  $keyMatrix  The key matrix.
     * @param  array  $vector  The vector to multiply.
     * @return array The resulting vector.
     */
    private function vectorMatrixMultiplication(array $keyMatrix, array $vector): array
    {
        $result = array_fill(0, count($keyMatrix), 0);

        for ($i = 0; $i < count($keyMatrix); $i++) {
            for ($j = 0; $j < count($keyMatrix); $j++) {
                $result[$i] += $keyMatrix[$i][$j] * $vector[$j];
                $result[$i] %= self::getSize();
            }
        }

        return $result;
    }

    /**
     * Loads the Hill cipher key matrix from the .env file.
     *
     * @param  bool  $previousKey  Whether to load the previous key matrix.
     * @return array The key matrix.
     */
    private function getKeyMatrix(bool $previousKey = false): array
    {
        // Retrieve the key from the .env file and convert it into an array
        $keyString = $previousKey ? config('hill-cipher.previous_key') : config('hill-cipher.key');

        $keyMatrix = json_decode($keyString, true);

        if (count($keyMatrix) != count($keyMatrix[0]) || json_last_error() !== JSON_ERROR_NONE || ! is_array($keyMatrix) || count($keyMatrix) < 2 || count($keyMatrix) > 3) {
            throw new InvalidArgumentException('Invalid Hill cipher key matrix in .env file.');
        }
        if (!$this->isKeyMatrixInvertible($keyMatrix)) {
            // tell the user key matrix is invertible and give random key matrix are not invertible
            $keyMatrix = $this->generateRandomInvertibleKeyMatrix(count($keyMatrix));

            throw new InvalidArgumentException(
                'Invalid Hill cipher key matrix in .env file.' . PHP_EOL . 'The key matrix is not invertible. A random invertible key matrix has been generated.'
                    . PHP_EOL . 'Please update the key matrix in the .env file with the following:' . PHP_EOL . json_encode($keyMatrix)
            );
        }
        return $keyMatrix;
    }

    /**
     * Generates a random invertible key matrix.
     *
     * @param  int  $size  The size of the key matrix.
     * @return array The random invertible key matrix.
     */
    private function generateRandomInvertibleKeyMatrix(int $size): array
    {
        while (true) {
            $keyMatrix = [];
            for ($i = 0; $i < $size; $i++) {
                $row = [];
                for ($j = 0; $j < $size; $j++) {
                    $row[] = rand(0, self::getSize() - 1);
                }
                $keyMatrix[] = $row;
            }

            if ($this->isKeyMatrixInvertible($keyMatrix)) {
                return $keyMatrix;
            }
        }
    }

    /**
     * Checks if the key matrix is invertible.
     *
     * @param  array  $keyMatrix  The key matrix.
     * @return bool Whether the key matrix is invertible.
     */
    private function isKeyMatrixInvertible(array $keyMatrix): bool
    {
        $determinant = $this->determinantMatrix($keyMatrix);
        return $this->gcd($determinant, self::getSize()) == 1;
    }

    /**
     * Calculates the greatest common divisor of two numbers.
     *
     * @param  int  $a  The first number.
     * @param  int  $b  The second number.
     * @return int The greatest common divisor.
     */
    private function gcd(int $a, int $b): int
    {
        return $b == 0 ? $a : $this->gcd($b, $a % $b);
    }
    /**
     * Encrypts a word using the Hill cipher algorithm and the provided key matrix.
     *
     * The Hill cipher algorithm works by encrypting the text in blocks of two characters.
     * If the text has an odd number of characters, a dummy character 'X' is added to the end.
     * The encrypted text is returned as a string.
     *
     * @param  string  $word  The word to encrypt.
     * @return string The encrypted word.
     */
    public function encrypt(string $word): string
    {
        $keyMatrix = $this->getKeyMatrix();
        $encryptedWord = '';

        // Add a dummy character to the end of the text if it has an odd number of characters
        while (strlen($word) % count($keyMatrix) != 0) {
            $word .= 'X'; // 'X' is the dummy character
        }
        for ($i = 0; $i < strlen($word); $i += count($keyMatrix)) {
            $chars = array_slice(str_split($word, 1), $i, count($keyMatrix));
            $checkSpecialCharacters = array_map(function ($char) {
                return preg_match('/[\s\d' . preg_quote(self::SPECIAL_CHARACTERS, '/') . ']/u', $char);
            }, $chars);
            // If the characters are spaces or numbers, add them directly to the encrypted word
            if (in_array(1, $checkSpecialCharacters)) {
                $encryptedWord .= implode('', $chars);
            } else {
                // Determine if the characters are uppercase or lowercase
                $isUpperCase = array_map('ctype_upper', $chars);
                $vector = array_map(function ($char) {
                    return $this->charToNumber(strtoupper($char));
                }, $chars);
                $encryptedVector = $this->vectorMatrixMultiplication($keyMatrix, $vector);
                foreach ($encryptedVector as $index => $num) {
                    $encryptedChar = $this->numberToChar($num);
                    // Convert back to original case
                    $encryptedWord .= $isUpperCase[$index] ? strtoupper($encryptedChar) : strtolower($encryptedChar);
                }
            }
        }

        return $encryptedWord;
    }

    /**
     * Decrypts an encrypted word using the Hill cipher algorithm and the provided key matrix.
     *
     * The Hill cipher algorithm works by decrypting the text in blocks of two characters.
     * If the text has an odd number of characters, a dummy character 'X' is added to the end.
     * The decrypted text is returned as a string.
     *
     * @param  string  $encryptedWord  The encrypted word to decrypt.
     * @param  bool  $previousKey  Whether to use the previous key matrix.
     * @return string The decrypted word.
     */
    public function decrypt(string $encryptedWord, bool $previousKey = false): string
    {
        $keyMatrix = $this->getKeyMatrix($previousKey);
        $decryptedWord = '';

        // Add a dummy character to the end of the text if it has an odd number of characters
        while (strlen($encryptedWord) % count($keyMatrix) != 0) {
            $encryptedWord .= 'X'; // 'X' is the dummy character
        }

        for ($i = 0; $i < strlen($encryptedWord); $i += count($keyMatrix)) {
            $chars = array_slice(str_split($encryptedWord, 1), $i, count($keyMatrix));
            // If the characters are spaces or numbers, add them directly to the decrypted word
            $checkSpecialCharacters = array_map(function ($char) {
                return preg_match('/[\s\d' . preg_quote(self::SPECIAL_CHARACTERS, '/') . ']/u', $char);
            }, $chars);
            if (in_array(1, $checkSpecialCharacters)) {
                $decryptedWord .= implode('', $chars);
            } else {
                // Determine if the characters are uppercase or lowercase
                $isUpperCase = array_map('ctype_upper', $chars);

                $vector = array_map(function ($char) {
                    return $this->charToNumber(strtoupper($char));
                }, $chars);
                $decryptedVector = $this->vectorMatrixMultiplication($this->inverseMatrix($keyMatrix), $vector);
                foreach ($decryptedVector as $index => $num) {
                    $decryptedChar = $this->numberToChar($num);
                    $decryptedWord .= $isUpperCase[$index] ? strtoupper($decryptedChar) : strtolower($decryptedChar);
                }
            }
        }

        while (substr($decryptedWord, -1) === 'X') {
            $decryptedWord = substr($decryptedWord, 0, -1);
        }

        return $decryptedWord;
    }

    /**
     * Encrypts the specified fields of a model.
     *
     * @param  array  $data  The model data.
     * @param  array  $fields  The fields to encrypt.
     * @return array The encrypted model data.
     */
    public function encryptModelData(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypts the specified fields of a model.
     *
     * @param  array  $data  The model data.
     * @param  array  $fields  The fields to decrypt.
     * @return array The decrypted model data.
     */
    public function decryptModelData(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Re-encrypts the specified fields of a model using the new key.
     *
     * @param  array  $data  The model data.
     * @param  array  $fields  The fields to re-encrypt.
     * @return array The re-encrypted model data.
     */
    public function reEncryptModelData(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $decryptedValue = $this->decrypt($data[$field], true);
                $data[$field] = $this->encrypt($decryptedValue);
            }
        }

        return $data;
    }
}
