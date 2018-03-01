<?php

/**
 * Classe per la gestione delle funzioni VALIDATE richiamabili del progetto.
 *
 * @since 2.4
 */
class VALIDATE
{
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

    	$access_key = Settings::get('apilayer API key');

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
			
			/*se la riposta è null verficando il formato, il record mx o il server smtp imposto a false*/
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

			/*echo "format_valid: ".$data->format_valid; 
			echo "<br>\n";
			echo "smtp_check: ".$data->smtp_check; 
			echo "<br>\n";
			echo "mx_found: ".$data->mx_found;*/
			
			//echo json_last_error(); 
			//echo json_last_error_msg();



		}


		/*return [
			'email' => $email,
	        'format_valid' => $data->format_valid,
	        'smtp_check' => $data->smtp_check,
	        'mx_found' => $data->mx_found,
	        'json_last_error_msg' => json_last_error_msg(),
         	'json_last_error' => json_last_error(),
        ];*/

        return $data;


	}
}
