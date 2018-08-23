<?php

namespace Blocker\Bip39\Util;

use Blocker\Bip39\Buffer\BitBuffer;
use Illuminate\Support\Collection;

/**
 * Class WordList.
 *
 * Implements access to the BIP39 word lists on a given locale.
 */
class WordList
{
    /**
     * @var array List of available langues.
     */
    protected $locales = [
        'en' => 'English',
        'fr' => 'French',
        'it' => 'Italian',
        'zh' => 'Chinese (simplified)',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'es' => 'Spanish',
    ];

    /**
     * @var string Current locale / dictionary name.
     */
    protected $locale = 'en';

    /**
     * @var Collection List of words in use for encoding / decoding.
     */
    protected $words;

    /**
     * WordList constructor.
     *
     * @param string $locale
     */
    public function __construct(string $locale = 'en')
    {
        // thrown an exception if there's no word list for the requested locale.
        if (!array_key_exists($locale, $this->locales)) {
            throw new \RuntimeException("Word list for the locale {$locale} is not available.");
        }

        // setups the current locale.
        $this->locale = $locale;

        // init the word list database.
        $this->words = $this->initWords();
    }

    /**
     * Path where a word list for a given locale lives.
     *
     * @return string
     */
    protected function wordListPath() : string
    {
        return  __DIR__."/../../data/{$this->locale}.txt";
    }

    /**
     * Retrieve the word list from disk.
     *
     * @return string
     */
    protected function getWordList() : string
    {
        return file_get_contents($this->wordListPath());
    }

    /**
     * Split the word list string into an array of words.
     *
     * @return array
     */
    protected function wordListToArray() : array
    {
        return explode("\n", trim($this->getWordList()));
    }

    /**
     * Init the list of words on the given locale.
     *
     * @return Collection
     */
    protected function initWords() : Collection
    {
        // get the array and make into a collection.
        return collect($this->wordListToArray());
    }

    /**
     * Returns what word lives under a given decimal / integer index.
     *
     * @param BitBuffer $index
     *
     * @return null|string
     */
    public function getWord(BitBuffer $index) : ?string
    {
        return $this->words->get($index->toDecimal(), null);
    }

    /**
     * Returns the decimal index for a given word.
     *
     * @param string $word
     *
     * @return null|BitBuffer
     */
    public function getIndex(string $word) : ?BitBuffer
    {
        // flip the array and search the index for the word
        $index = $this->words->flip()->get($word);

        // if found, return as BitBuffer instance, null otherwise.
        return $index ? BitBuffer::fromDecimal($index) : null;
    }
}