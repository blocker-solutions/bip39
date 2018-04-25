# PHP BIP39

[![Latest Stable Version](https://poser.pugx.org/blocker/bip39/v/stable)](https://packagist.org/packages/blocker/bip39)
[![License](https://poser.pugx.org/blocker/bip39/license)](https://packagist.org/packages/blocker/bip39)

An easy to use, multilingual, stand alone, and blockchain independent implementation of the **[BIP39](https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki) proposal for PHP.

This library allows encoding and decoding data, on the 128-256 bit range into Mnemonic word lists.

Have you ever used a local Bitcoin wallet? remember those 12 words you type to backup your private key? This library allows that functionality on PHP. 

## 1. Background.

This library was built with both, production usage readiness and conceptual learning in mind.

It means all code is over commented with detailed information about each single aspect of encoding and decoding BIP39 word sequences.  

## 2. Install:

Standard composer installation:

```bash
composer require blocker/bip39
```

## 3. Concepts:

While implementing this library in your projects, you will need to understand some key factors:


### 3.1. Entropy:

On this library implementation, we call entropy a given set of data, which bits are within the 128 bit to 256 bit.

This means, for example, that you may generate a 256 bit private key for `ECDSA`, and allow your users to encode this random, hard to remember key into a set of words.

Ways for obtaining entropy:

If you already have a value you want to encode:

```php
<?php

// aliases.
use Blocker\Bip39\Util\Entropy;

$entropy = new Entropy($dataInHexadecimal);
```

or, if you want to generate some data to then create a private key, one could:
 
```php
<?php

// aliases.
use Blocker\Bip39\Util\Entropy;

// the parameter here is the size, in bits, of the random data to be generated.
// values can be between 128 and 256, and must be multiples of 32.
$entropy = Entropy::random(128);

```

### 3.2. Encoding and Decoding.

Just as simple as using the entropy, parsing from entropy into a word sequence and vice versa is really easy:

```php

// aliases.
use Blocker\Bip39\Bip39;
use Blocker\Bip39\Util\Entropy;

// a word sequence provided by the user.
$some128bitValueAlreadyEncoded = 'walnut antenna forward shuffle invest legal confirm polar hope timber pear cover';

// create a bip39 instance.
$bip39 = new Bip39('en'); 

// decode the given word list into an entropy instance.
$entropy = $bip39->decode($some128bitValueAlreadyEncoded);

// decode the provided word sequence into a hexadecimal encoded entropy.
echo (string) $entropy; // "f6c1396f63b75efecbbd3b6d7c468818"
```

And, just as easy as encoding:

```php

// aliases.
use Blocker\Bip39\Bip39;
use Blocker\Bip39\Util\Entropy;

// some entropy value to be encoded with BIP39.
$previousGeneratedEntropyHex = 'f6c1396f63b75efecbbd3b6d7c468818';

//$some128bitValueAlreadyEncoded = 'walnut antenna forward shuffle invest legal confirm polar hope timber pear cover';

// create a bip39 instance.
$bip39 = new Bip39('en'); 

// create an entropy instance from it's hex representation.
$entropy = new Entropy($previousGeneratedEntropyHex);

echo (string) $bip39->setEntropy($entropy)->encode();
// 'walnut antenna forward shuffle invest legal confirm polar hope timber pear cover'

``` 

Easy right?

### 3.3. Supported Languages:

- [en] English
- [es] Spanish
- [fr] French
- [it] Italian
- [ja] Japanese
- [ko] Korean
- [zh] Chinese (Simplified).

Just use the language locale on the Bip39 constructor:

```php
$bip39 = new Bip39('en');
$bip39 = new Bip39('es');
$bip39 = new Bip39('fr');
// ...
```

### 3.4. Special Features.

As mentioned earlier, this library was built with education also in mind, so, there's a buffered binary operations class, classed `BitBuffer` and some utils that will help you understand a lot of different concepts.

Good Leaning!
