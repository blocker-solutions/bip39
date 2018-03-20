<?php

namespace Blocker\Bip39\Util;

use Blocker\Bip39\Buffer\BitBuffer;

/**
 * Class Hash.
 *
 * Wrapper for the SHA-256 and possible other hash algorithms.
 */
class Hash
{
    /**
     * Creates SHA-256 hash of a given value.
     *
     * @param BitBuffer $value Value to hash as SHA-256.
     *
     * @return BitBuffer The hash value.
     */
    public static function sha256(BitBuffer $value) : BitBuffer
    {
        // returns the hex hash for the value.
        return BitBuffer::fromRaw(hash('sha256', $value->toRaw(), true));
    }
}