<?php
	
	$postdata = 
				    array(
				        'wu_loginname' => '140946',
				        'wu_password' => 'emnwpxnz'
				    )
				;

	$url = 'https://leerlingen.trinitascollege.nl/Login';

	$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
	$header[] = "Cache-Control: max-age=0";
	$header[] = "Connection: keep-alive";
	$header[] = "Keep-Alive: timeout=5, max=100";
	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
	$header[] = "Accept-Language: en-us,en;q=0.5";
	$header[] = ""; // BROWSERS USUALLY LEAVE BLANK

	$curl = curl_init ();
	curl_setopt($curl, CURLOPT_URL, $url);

	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:37.0) Gecko/20100101 Firefox/37.0");
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
	curl_setopt($curl, CURLOPT_VERBOSE, 1);
	curl_setopt($curl, CURLOPT_COOKIEFILE, getcwd().'/cookies.txt');
	curl_setopt($curl, CURLOPT_COOKIEJAR, getcwd().'/cookies.txt');
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_exec($curl);
	curl_setopt($curl, CURLOPT_URL, "https://leerlingen.trinitascollege.nl/Portaal/Persoonlijke_info/Rapport_cijfers?wis_ajax&ajax_object=7249&view=print");
	curl_exec($curl);

	$infos = curl_getinfo($curl);

	curl_close ( $curl );

	echo $curlData;

?>