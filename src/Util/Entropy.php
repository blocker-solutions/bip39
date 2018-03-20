<?php

namespace Blocker\Bip39\Util;

use Blocker\Bip39\Buffer\BitBuffer;

/**
 * Class Entropy.
 *
 * Handles entropy parsing and generation.
 */
class Entropy
{
    /**
     * @var BitBuffer Entropy buffer.
     */
    protected $entropy;

    /**
     * @var string Binary string representation of the entropy value.
     */
    protected $binaryString;

    /**
     * Constant integer to divide the entropy by.
     */
    CONST CHECKSUM_SIZE_DIVISOR = 32;

    /**
     * Minimum size, in bits, the entropy must have.
     */
    CONST MIN_ENTROPY_BIT_SIZE = 128;

    /**
     * Maximum size, in bits, the entropy must have.
     */
    CONST MAX_ENTROPY_BIT_SIZE = 256;

    /**
     * Default size, in bits, for generated entropy values.
     */
    CONST DEFAULT_GENERATED_ENTROPY_SIZE = 128;

    /**
     * Just so we are doing many constants, add one more to avoid hard coded values.
     */
    CONST BITS_IN_A_BYTE = 8;

    /**
     * Entropy constructor.
     *
     * @param string $hexEntropy
     */
    public function __construct(string $hexEntropy)
    {
        // assign the entropy value on the instance.
        $this->entropy = BitBuffer::fromHex($hexEntropy);

        // set the binary string representation of the entropy.
        $this->binaryString = $this->entropy->toBinary();
    }

    /**
     * Returns the current hexadecimal entropy value.
     *
     * @return string
     */
    public function getEntropyHex() : string
    {
        return $this->entropy->toHex();
    }
    /**
     * Generates a random entropy value and returns a wrapped instance of it.
     *
     * @param null|int $size
     *
     * @throws \Exception
     *
     * @return Entropy
     */
    public static function random(int $size = null) : Entropy
    {
        // if no size was requested, use the default const value.
        $size = $size ?? self::DEFAULT_GENERATED_ENTROPY_SIZE;

        // convert the bit size into bytes.
        $bytes = $size / self::BITS_IN_A_BYTE;

        try {
            // try generating the random bytes of entropy.
            $randomBytes = random_bytes($bytes);
        } catch (\Exception $e) {
            // throw a custom exception (avoid leaking parts of the original exception).
            throw new \RuntimeException('Error generating a random entropy.');
        }

        // convert the random binary data to hexadecimal and
        // return a new instance of the entropy class.
        return new self(BitBuffer::fromRaw($randomBytes)->toHex());
    }

    /**
     * Given a seed, extracts the entropy instance which generated the seed.
     *
     * @param BitBuffer $seed Seed value, generated from the word sequence.
     *
     * @return Entropy Instance of the entropy (extracted).
     *
     * @throws \Exception
     */
    public static function fromSeed(BitBuffer $seed) : Entropy
    {
        // detect the actual entropy size, without the checksum.
        $entropySize = floor($seed->getSize() / self::CHECKSUM_SIZE_DIVISOR) * self::CHECKSUM_SIZE_DIVISOR;

        // slice the entropy out of the seed, meaning, strip out the checksum.
        $entropyValue = $seed->sliceBits(0, $entropySize);

        // creates an entropy instance, from the hex value.
        $entropy = new self($entropyValue->toHex());

        // if the seed provided does not match the seed parsed, it means the checksum
        // is not equal and by this reason, there was a problem parsing the word sentence
        // into the original entropy.
        if ($entropy->getSeed()->toHex() !== $seed->toHex()) {
            throw new \Exception('The seed was recovered, but the checksum mismatches.');
        }

        // return the entropy instance, after the checksum match.
        return $entropy;
    }

    /**
     * Generates the binary string from the hexadecimal entropy value.
     *
     * @param BitBuffer $entropy
     *
     * @return string
     */
    protected function makeBinaryString(BitBuffer $entropy)
    {
        return $entropy->toBinary();
    }

    /**
     * Returns the size, in bits of the entropy.
     *
     * @return int
     */
    public function getSize(): int
    {
        // detect the actual entropy size, without the checksum.
        $entropySize = floor($this->entropy->getSize() / self::CHECKSUM_SIZE_DIVISOR) * self::CHECKSUM_SIZE_DIVISOR;

        return $entropySize;
    }

    /**
     * Checksum bit size is composed of the entropy bit length divided by the constant 32.
     *
     * @return float|int
     */
    public function getChecksumSize()
    {
        return ceil(($this->getSize() / self::CHECKSUM_SIZE_DIVISOR) / 4) * 4;
    }

    /**
     * Generates a SHA-256 hash of the entropy value.
     *
     * @return BitBuffer
     */
    public function getHash() : BitBuffer
    {
        // make the hash and return.
        return Hash::sha256($this->entropy);
    }

    /**
     * Checks if the current entropy size is within the valid range.
     *
     * @return bool
     */
    public function withinRange() : bool
    {
        // get the size in bits of the current entropy.
        $size = $this->getSize();

        // the entropy size must be within the MIN and MAX range.
        return ($size >= self::MIN_ENTROPY_BIT_SIZE && $size <= self::MAX_ENTROPY_BIT_SIZE);
    }

    /**
     * Checks for a valid entropy size (multiples of the checksum divisor).
     *
     * @return bool
     */
    public function validMultipleOfDivisor() : bool
    {
        // after dividing the entropy bit size by the checksum divisor, the rest must be zero.
        return ($this->getSize() % self::CHECKSUM_SIZE_DIVISOR) === 0;
    }

    /**
     * Checks if the entropy bit size is valid for usage on BIP39.
     * @return bool
     */
    public function validForBIP39() : bool
    {
        return ($this->withinRange() && $this->validMultipleOfDivisor());
    }

    /**
     * Get a BIP39 checksum in hex for a given entropy.
     *
     * @return BitBuffer
     */
    public function getChecksum() : BitBuffer
    {
        // get the entropy hashed value.
        $hash = $this->getHash();

        // extract the first N bytes from the hash.

        $checksum = $hash->sliceBits(0, $this->getChecksumSize());

        // return the hex checksum.
        return $checksum;
    }

    /**
     * Generates the entropy seed from the entropy itself, appending the checksum value.
     *
     * @return BitBuffer
     */
    public function getSeed() : BitBuffer
    {
        return $this->entropy->append($this->getChecksum(), $this->getSize(), $this->getChecksumSize());
    }

    /**
     * Returns the hex value of the entropy.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getEntropyHex();
    }
}