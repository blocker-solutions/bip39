<?php

namespace Blocker\Bip39\Buffer;

use Illuminate\Support\Collection;
use GMP;

/**
 * Class Buffer.
 *
 * This class was inspired by the https://github.com/Bit-Wasp/buffertools-php project.
 *
 * This implementation is focused on operations on bit level, instead of byte level.
 *
 * Background information on all things related to base conversions done by this library:
 *
 * BIP39 itself is a very short Bitcoin proposal. It does not specify very clearly the
 * technical aspects, but, all information there is enough so people have agreed on a implementation.
 *
 * BIP39, which is used, for instance on the famous Copay wallet, utilizes 3 positional numeral systems.
 * You may know the positional numeral systems by it's widely adopted radix (bases)
 *
 * So, for BIP39, we need to employ this 3 numeral systems.
 *
 * 1. Base16 [Hexadecimal]: Used to easily display or store the seeds and private keys based on BIP39.
 *    Since the Hexadecimal N.S. has 16 possibilities, 1 character on Base16 represents
 *    half byte, which is itself 4 bit, and commonly called "nibble"
 *
 * 2. Base2 [Binary]: Yes, the one with only zeros and ones.
 *    Binary notation, or Base2 is used here to manipulate each bit of a value, which is needed by
 *    BIP39.
 *    Since we already know that one character on the Base16 represents half byte, we can now
 *    deduce that one character on Base16, is equal 4 characters on Base2.
 *    The biggest catch you need to understand from Base2 is this:
 *    Since 1 bit == 1 character, we can safely extract characters from the Base2 string and also
 *    we can verify the size of a given value in bits, by counting it's character length.
 *
 * 3. Base10 [Decimal]: The plain old 10 number system.
 *    Base10 is only used on this library to address on a list consisting of 2048 words.
 *    Each part of a BIP39 seed is split into 11bit parts, and the Base10 (decimal) value of that
 *    Given part represents the word number on that list (start from zero, of course).
 */
class BitBuffer
{
    /**
     * @var string string Normalized binary representation of the value.
     */
    protected $value;

    /**
     * @var int Current size (left zero padded even) of the value.
     */
    protected $size;

    /**
     * @var GMP initialized value for better memory consumption (constant single instance).
     */
    protected $gmp;

    /**
     * BitBuffer constructor.
     *
     * @param string $value
     */
    public function __construct(string $value = '')
    {
        // calculate the binary normalized length of data.
        $this->size = mb_strlen($value);

        // set the current value as a normalized binary string.
        $this->value = $this->normalizeBinaryString($value);

        // init the value as GMP.
        $this->gmp = gmp_init($this->value, 2);
    }

    /**
     * Create an instance of the BitBuffer from a gmp resource instance.
     *
     * @param \resource $gmp GMP instance to parse.
     *
     * @return BitBuffer
     */
    public static function fromGmp($gmp) : BitBuffer
    {
        return new BitBuffer(gmp_strval($gmp, 2));
    }

    /**
     * Returns the internal GMP initialized resource.
     *
     * @return \resource
     */
    public function toGmp()
    {
        return $this->gmp;
    }

    /**
     * Returns the base10 (decimal) representation of the value.
     *
     * @param string $decimalString String is expected because of big integer representation.
     *
     * @return BitBuffer
     */
    public static function fromDecimal(string $decimalString): BitBuffer
    {
        return BitBuffer::fromGmp(gmp_init($decimalString, 10));
    }

    /**
     * Returns the base10 (decimal) representation of the value.
     *
     * @return string
     */
    public function toDecimal(): string
    {
        return gmp_strval($this->toGmp(), 10);
    }

    /**
     * Creates a BitBuffer instance, from a given hexadecimal (base16) string.
     *
     * @param string $hexValue
     *
     * @return BitBuffer
     */
    public static function fromHex(string $hexValue): BitBuffer
    {
        // creates a gmp resource from the given hex value.
        return BitBuffer::fromGmp(gmp_init($hexValue, 16));
    }

    /**
     * Returns the base16 (hexadecimal) representation of the value.
     *
     * @return string
     */
    public function toHex(): string
    {
        return gmp_strval($this->toGmp(), 16);
    }

    /**
     * This method is an alias of the constructor only, since the expected input on
     * the constructor is already a binary string (base2 encoded string)
     *
     * @param string $binaryString The binary string "buffer"
     *
     * @return BitBuffer Returns a factories buffer instance.
     */
    public static function fromBinary(string $binaryString): BitBuffer
    {
        return new self($binaryString);
    }

    /**
     * Returns the internal binary string reference.
     *
     * NOTICE: For the raw un-encoded data, call the ->toRaw() method instead.
     *
     * On the base2 (binary), one char means 1 bit.
     *
     * @param null|int $length
     *
     * @return string
     */
    public function toBinary(int $length = null): string
    {
        return $this->normalizeBinaryString($this->value, $length);
    }

    /**
     * Parses the raw data (not human readable) into a BitBuffer instance.
     *
     * @param string $rawBinary
     *
     * @return BitBuffer
     */
    public static function fromRaw(string $rawBinary): BitBuffer
    {
        // create a hexadecimal representation of the data.
        $hexData = bin2hex($rawBinary);

        // call the hex factory method instead of repeat it's logic.
        return BitBuffer::fromHex($hexData);
    }

    /**
     * This method returns the binary data itself, without any kind of encoding.
     *
     * The output of this method is usually not human readable.
     *
     * @return string
     */
    public function toRaw(): string
    {
        return hex2bin($this->toHex());
    }

    /**
     * Returns the current value size in bits.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Normalize a binary string.
     *
     * GMP and PHP and BIT concatenation: Source of problems.
     *
     * BIP39, splits the bits, in groups of 11 bits.
     *
     * The problem starts when parsing those groups from their decimal / integer representation.
     *
     * The final string, must be one more, 11 bits, or even better, 11 chars, since
     * on the base2 1 char = 1 bit.
     *
     * So when parsing base2 strings back, if the slice contained some leading zeros, like this one:
     *
     * "00110001110"
     *
     * When parsing back, the value itself, get's converter to integer as it should be,
     * but the actual serialization back into base2, is actually:
     *
     * "110001110"
     *
     * Since there's not use for the zeros on the left side.
     *
     * Once this occurs, it's must required to normalize it.
     *
     * Meaning, we need to pint the base2 string with the original number of zeros, and there's no deterministic
     * way of doing that just by guessing.
     *
     * So, when parsing the values from a Mnemonic value, each chunk needs to be
     * explicitly padded accordingly.
     *
     * It means, we need to pad that value to it's original 11 bits and just then concatenate.
     *
     * @param string $value Binary string to normalize.
     * @param null|int $bitSize When joining strings, pad with the bit
     *
     * @return string Normalized binary string value.
     */
    protected function normalizeBinaryString(string $value, int $bitSize = null): string
    {
        // pad until the actual size, or until a requested limit.
        $padSize = ($bitSize ?? $this->size);

        // pad and return the normalized binary string.
        return str_pad($value, $padSize, '0', STR_PAD_LEFT);
    }

    /**
     * Imagine this method as the bit level "substr".
     *
     * One binary data, the mb_substr can be used to get a given bit, since each bit = char/
     *
     * For common ASCII UTF-8 values, slicing bits will not have the same effect since
     * on UTF-8, an slice of 4 bits will crop half of a character.
     *
     * @param int $start The first bit of the slice
     * @param int $length How many bits from the start will be the slice end.
     *
     * @return BitBuffer
     */
    public function sliceBits(int $start, int $length): BitBuffer
    {
        // get a binary string from the current instance.
        $binaryStringValue = $this->toBinary();

        // slice the requested bits.
        $extractedBits = mb_substr($binaryStringValue, $start, $length);

        // return a new BitBuffer instance on the slice.
        return new BitBuffer($extractedBits);
    }

    /**
     * Split the current value into several chunks, with the size of each chunk being
     * set on the $sliceSize parameter.

     * @param int $sliceSize The amount of bits each slice will have.
     *
     * @return Collection Collection of all slices.
     */
    public function splitBits(int $sliceSize): Collection
    {
        // split the binary string into pieces of a given size.
        // the resulting array is then used to create a collection instance.
        $slices = collect(str_split($this->toBinary(), $sliceSize));

        // map the collection with the binary string pieces...
        return $slices->map(function (string $slice) {
            // so each of the pieces get transformed into a BitBuffer instance also.
            return BitBuffer::fromBinary($slice);
        });

    }

    /**
     * Split the current value into several chunks, with the size of each chunk being
     * set on the $sliceSize parameter.
     *
     * @see normalizeBinaryString for comments on the bitSize attribute.
     *
     * @param array $slices Slices to join.
     * @param int $bitSize. Number of bits required on each chunk.
     *
     * @return BitBuffer Joined buffered value..
     */
    public static function joinBits(array $slices, int $bitSize = null): BitBuffer
    {
        // creates a mapping function.
        $mapper = function (BitBuffer $slice) use ($bitSize) {
            return $slice->toBinary($bitSize);
        };

        // creates the padded slices strings.
        $resultingBinaryString = collect($slices)->map($mapper)->implode('');

        // return the resulting binary string as a new BitBuffer instance.
        return new BitBuffer($resultingBinaryString);
    }

    /**
     * Append a given value on the current value.
     *
     * This is similar to concat, but at bit level.
     *
     * This is intended for checksum / seed generation and be aware that appending bits on any of the sides
     * will dramatically change the inner value.
     *
     * @param BitBuffer $append Value to append.
     * @param int $currentSize
     * @param int $appendSize
     *
     * @return BitBuffer
     */
    public function append(BitBuffer $append, int $currentSize, int $appendSize): BitBuffer
    {
        // just concatenate the two binary strings.
        $newBinaryValue = "{$this->toBinary($currentSize)}{$append->toBinary($appendSize)}";

        // returns a new BitBuffer consisting of the appended string.
        return new BitBuffer($newBinaryValue);
    }

    /**
     * Simple method for rendering a BitBuffer instance as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHex();
    }
}