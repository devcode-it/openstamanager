/**
 * Classes for encryption on STU devices
 **/

function toHex(value, padding) {
  var hex = value.toString(16);
  return "0000000000000000".substr(0,padding-hex.length)+hex;
}

function toHex2(value) { return toHex(value,2); }
function toHex4(value) { return toHex(value,4); }
function toHex8(value) { return toHex(value,8); }

function arrayToHex(v) {
  var s="";
  for (var i = 0; i < v.length; ++i)
    s = s + toHex2(v[i]);
  return s;
}

function hexToArray(s) {
  var a = new Array();
  for (var i = 0; i < s.length;i+=2)
    a.push(parseInt("0x"+ s.substr(i,2),16));
  return a;
}

function padLeft(str, len, pad) {
  if (typeof(pad) == "undefined") pad = ' ';
  str = str.toString();
  if (len > str.length)
    str = Array(len+1-str.length).join(pad) + str;
  return str;
}

function base64UrlDecode(str) {
  str = atob(str.replace(/-/g, '+').replace(/_/g, '/'));
  var buffer = new Array(str.length);
  for(var i = 0; i < str.length; ++i) {
    buffer[i] = str.charCodeAt(i);
  }
  return buffer;
}

function arrayEquals(a, b) {
  return Array.isArray(a) &&
    Array.isArray(b) &&
    a.length === b.length &&
    a.every((val, index) => val === b[index]);
}

function generateHexString(length) {
  var ret = "";
  while (ret.length < length) {
    ret += Math.random().toString(16).substring(2);
  }
  return ret.substring(0,length);
}

function powMod(a, b, prime) {
    if (b <= BigInt(0)) {
        return (BigInt(1));
    } else if (b === BigInt(1)) {
        return a % prime;
    } else if (b % BigInt(2) === BigInt(0)) {
        return powMod((a * a) % prime, b / BigInt(2) | BigInt(0), prime) % prime;
    } else {
        return (powMod((a * a) % prime, b / BigInt(2) | BigInt(0), prime) * a) % prime;
    }
}

class MyEncryptionHandler {
	
	constructor() {
		this.clearKeys();
	}
	
	/**
     * Reset the encryption handler
     */
    reset() {
	    this.clearKeys();
	}	

    /**
     * Reset all encryption key values
     */
    clearKeys() {
		this.bigint_p    = null;
		this.bigint_g    = null;
		this.sjcl_keyAES = null;
	}

    /**
     * Checks if Diffie-Hellman key exchange is required
     * @return true if key exchange is required
     */
    requireDH() {
		return this.bigint_p == null || this.bigint_g == null; 
	}

    /**
     * Initializes parameters for Diffie-Hellman key exchange 
     * @param  dhPrime   Diffie-Hellman prime number
     * @param  dhBase    Diffie-Hellman base number
     */
    setDH(dhPrime, dhBase) {
		var p = dhPrime;
		var g = dhBase;

		this.bigint_p = BigInt("0x"+arrayToHex(p));
		this.bigint_g = BigInt("0x"+arrayToHex(g));
	}

    /**
     * Generate a public key
     * @return 128-bit key, as array of bytes
     */
    generateHostPublicKey() {
		// secret key
		let randomValues = new Uint8Array(64);
		window.crypto.getRandomValues(randomValues);
		this.bigint_a = BigInt("0x"+arrayToHex(randomValues));

		// public key
		var bigint_A = powMod(this.bigint_g, this.bigint_a, this.bigint_p);

		var hex_A = padLeft(bigint_A.toString(16), 32, '0');
		var A = hexToArray(hex_A);
		return A;
	}

    /**
    * Calculate a shared key, given the tablet's public key
    * @param  devicePublicKey   the tablet's public key
    */
    computeSharedKey(devicePublicKey) {
		var B = devicePublicKey;
  
		var bigint_B = BigInt("0x"+arrayToHex(B));

		var bigint_shared = powMod(bigint_B, this.bigint_a, this.bigint_p);

		var str_shared = padLeft(bigint_shared.toString(16), 32, '0');

		this.sjcl_keyAES = new sjcl.cipher.aes( sjcl.codec.hex.toBits(str_shared) );
	}

    /**
     * Decrypts a block of encrypted data
     * @param  data  an array of bytes to decrypt
     * @return decrypted data
     */
    decrypt(data) {
		var arr_cipherText  = data;
		var hex_cipherText  = arrayToHex(arr_cipherText);
		var sjcl_cipherText = sjcl.codec.hex.toBits(hex_cipherText);

		var sjcl_plainText = this.sjcl_keyAES.decrypt(sjcl_cipherText);

		var hex_plainText = sjcl.codec.hex.fromBits(sjcl_plainText);
		var arr_plainText = hexToArray(hex_plainText);
		return arr_plainText;
	}
}


class MyEncryptionHandler2 {

	constructor() {
		this.clearKeys();
	}
	
	/**
     * Reset the encryption handler
     */
    reset() {
		this.clearKeys();
	}

    /**
     * Reset all encryption key values
     */
    clearKeys() {
		this.privateKey = null;
		this.keyAES = null;
		this.exponent = null;
		this.modulus = null;		
	}

    /**
     *  Returns the symmetric key type
     * @return a Protocol.SymmetricKeyType value
     */
    getSymmetricKeyType() {
		return com.WacomGSS.STU.Protocol.SymmetricKeyType.AES256;
	}

    /**
     *  Returns the asymmetric padding type
     * @return a Protocol.AsymmetricPaddingType value
     */
    getAsymmetricPaddingType() {
		return com.WacomGSS.STU.Protocol.AsymmetricPaddingType.OAEP;
	}

    /**
     * Returns the asymmetric key type
     * @return a Protocol.AsymmetricKeyType value
     */
    getAsymmetricKeyType() {
		return com.WacomGSS.STU.Protocol.AsymmetricKeyType.RSA2048;
	}
	
    /**
     * Returns the public key exponent
     * @return RSA public key exponent as a byte array
     */
    async getPublicExponent() {
		const keyPair = await window.crypto.subtle.generateKey({
			name: "RSA-OAEP",
			modulusLength: 2048,
			publicExponent: new Uint8Array([0x01, 0x00, 0x01]),
			hash: {
				name: "SHA-1"
			},
		},
		true, //wheter the key is extractable or not
		["encrypt", "decrypt"]);

        this.privateKey = keyPair.privateKey;		
		const publicKey = await window.crypto.subtle.exportKey("jwk", keyPair.publicKey);
		
		// base64url-decode modulus
        this.modulus = base64UrlDecode(publicKey.n);

        // base64url-decode exponent
        this.exponent = base64UrlDecode(publicKey.e);

		return this.exponent;
	}

    /**
     * Generates a public key
     * @return generated key as a byte array
     */
    async generatePublicKey() {
		return this.modulus
	}

    /**
     * Uses private key and padding type to decrypt an encrypted AES (symmetric) key to use in
     * subsequent calls to #decrypt.
     * @param  data Encrypted AES key
     */
    async computeSessionKey(data) {
		const key = await window.crypto.subtle.decrypt({
				name: "RSA-OAEP"				
			},
			this.privateKey,
			Uint8Array.from(data)
		);	
		
		// replace additional left zeros
		const decryptKey = BigInt("0x"+arrayToHex(new Uint8Array(key)));				
		const hexKey = padLeft(decryptKey.toString(16), 64, '0');
		
		// SubtleCrypto only supports AES-CBC with PKCS#7 padding.
        // so we need to use another library as STU devices uses no padding.
        this.keyAES = new sjcl.cipher.aes(sjcl.codec.hex.toBits(hexKey));		
	}

    /**
     * Decrypts a block of encrypted data
     * @param  data  an array of bytes to decrypt
     * @return decrypted data
     */
    decrypt(data) {			
		var hex_cipherText  = arrayToHex(data);
		var sjcl_cipherText = sjcl.codec.hex.toBits(hex_cipherText);

		var sjcl_plainText = this.keyAES.decrypt(sjcl_cipherText);

		var hex_plainText = sjcl.codec.hex.fromBits(sjcl_plainText);
		var arr_plainText = hexToArray(hex_plainText);

		return arr_plainText;
	}
}
