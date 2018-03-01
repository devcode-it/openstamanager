<?php

/**
 * Classe per la gestione delle funzioni VALIDATE richiamabili del progetto.
 *
 * @since 2.4
 */
class VALIDATE
{
    

	/**
     * Controlla se la partita iva inserita è valida.
     *
     * @param string $vat_number
     *
     * @return object
     */
    public static function isValidVatNumber($vat_number)
    {

    	$access_key = Settings::get('apilayer API key for VAT number');

    	if ((!empty($vat_number)) and (!empty($access_key))){

    		if (strpos($vat_number, 'IT') === false) {
    			$vat_number = 'IT'.$vat_number;
    		}

    		$ch = curl_init();

			$qs = "&vat_number=" . urlencode(strtoupper($vat_number));

			$url = "http://apilayer.net/api/validate?access_key=$access_key" . $qs;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			

			$data = json_decode(curl_exec($ch));
			curl_close($ch);

			/*se la riposta è null imposto la relativa proprietà dell'oggetto a 0*/
			if ($data->valid==null)
				$data->valid = 0;

			//$data->url = $url;
			//$data->json_last_error = json_last_error();
			//$data->json_last_error_msg = json_last_error_msg();


    	}
		


    	return $data;

    }





    /**
     * Controlla se l'email inserita è valida.
     *
     * @param string $email
     * @param bool $format
     * @param bool $smtp
     *
     * @return object
     */
    public static function isValidEmail($email, $format = 1, $smtp = 0)
    {

    	$access_key = Settings::get('apilayer API key for Email');

    	/*$data = (object) [
		    'format_valid' => NULL,
		    'mx_found' => NULL,
		    'smtp_check' => NULL,
		];*/

	    if ((!empty($email)) and (!empty($access_key))){

			$ch = curl_init();

			$qs = "&email=" . urlencode($email);
			$qs .=  "&smtp=$smtp";
			$qs .= "&format=$format";

			$url = "http://apilayer.net/api/check?access_key=$access_key" . $qs;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			

			$data = json_decode(curl_exec($ch));
			curl_close($ch);
			
			/*se la riposta è null verficando il formato, il record mx o il server smtp imposto la relativa proprietà dell'oggetto a 0*/
			if (($data->format_valid==null)and($format))
				$data->format_valid = 0;

			if (($data->mx_found==null)and($smtp))
				$data->mx_found = 0;

			if (($data->smtp_check==null)and($smtp))
				$data->smtp_check = 0;

			
			/*controllo o meno smtp 
			if ($data->smtp_check==false)
				$data->smtp_check = 0;

			if ($data->mx_found==false)
				$data->mx_found = 0;
			*/
			/* --- */

			
			
			$data->json_last_error = json_last_error();
			$data->json_last_error_msg = json_last_error_msg(); 
			


		}

		
    	return $data;
  
	}
}
