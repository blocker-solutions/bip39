<?php

namespace Blocker\Bip39;

use Blocker\Bip39\Buffer\BitBuffer;
use Blocker\Bip39\Util\Entropy;
use Blocker\Bip39\Util\WordList;

/**
 * Class Bip39.
 *
 * Encoder, decoder of values (entropy) into BIP39 Mnemonic deterministic keys.
 */
class Bip39
{
    /**
     * @var Entropy Instance of the entropy
     */
    protected $entropy = null;

    /**
     * @var string Locale to use when generating or parsing the seeds.
     */
    protected $locale = 'en';

    /**
     * @var WordList Implementation of the word list reader.
     */
    protected $wordList;

    /**
     * Define the size of a slice that will produce a word on the Mnemonic result.
     */
    CONST SLICE_SIZE = 11;

    /**
     * Bip39 constructor.
     *
     * @param string $locale Locale to use when generating or parsing the word list.
     */
    public function __construct(string $locale = 'en')
    {
        // assign locale.
        $this->locale = $locale;

        // assign word list.
        $this->wordList = new WordList($locale);
    }

    /**
     * Set the entropy, from a hex value.
     *
     * @param Entropy $entropy
     *
     * @return Bip39
     */
    public function setEntropy(Entropy $entropy)
    {
        $this->entropy = $entropy;

        return $this;
    }
    /**
     * Return the entropy instance.
     *
     * @return Entropy|null
     */
    public function getEntropy(): ?Entropy
    {
        return $this->entropy;
    }

    /**
     * Generates the seed from the current entropy instance.
     *
     * @return null|BitBuffer
     */
    public function getSeed(): ?BitBuffer
    {
        return $this->entropy ? $this->entropy->getSeed() : null;
    }

    /**
     * Encode the seed under BIP39 Mnemonic sequence of words.
     *
     * @return string
     */
    public function encode() : string
    {
        // get the seed hex string.
        $seed = $this->getSeed();

        // slice the seed into parts of 11 bits each.
        $indexes = $seed->splitBits(self::SLICE_SIZE);

        // map the array of words into a string word list collection.
        $words = $indexes->map(function (BitBuffer $index) {
            // return the word that lives under the index N.
            return $this->wordList->getWord($index);
        });

        // import with a space.
        return $words->implode(' ');
    }

    /**
     * Simple encode alias for returning an array of elements.
     *
     * @return array
     */
    public function encodeArray() : array
    {
        // mind exploder.
        return explode(' ', $this->encode());
    }

    /**
     * Transform a Mnemonic word sequence into the original entropy.
     *
     * @param string $wordSequence
     *
     * @return Entropy
     *
     * @throws \Exception
     */
    public function decode(string $wordSequence) : Entropy
    {
        // break the list of words into an array and collect.
        $wordsCollection = collect(explode(' ', $wordSequence));

        // creates a mapper that will extract the values from the words.
        $mapper = function (string $word) {
            return $this->wordList->getIndex($word);
        };

        // get the array (collection) of slices that will compose the seed.
        $seedSlices = $wordsCollection->map($mapper)->toArray();

        // join the seed slices back into a unique value
        // this part is where the slicing into 11 bits pieces get reversed.
        $seed = BitBuffer::joinBits($seedSlices, self::SLICE_SIZE);

        // finally, returns the entropy, constructed from the seed value.
        return Entropy::fromSeed($seed);
    }

}