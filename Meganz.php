<?php
 class Meganz {
 public function base64url_encode($data) {
   return strtr(rtrim(base64_encode($data), '='), '+/', '-_');
 }
 public function str_to_a32($b) {
   // Add padding, we need a string with a length multiple of 4
   $b = str_pad($b, 4 * ceil(strlen($b) / 4), "\0");
   return array_values(unpack('N*', $b));
 }
 public function base64url_decode($data) {
   if (($s = (2 - strlen($data) * 3) % 4) < 2) $data .= substr(',,', $s);
   return base64_decode(strtr($data, '-_,', '+/='));
 }
 public function a32_to_str($hex) {
   return call_user_func_array('pack', array_merge(array('N*'), $hex));
 }
 public function dec_attr($attr, $key) {
   $b = trim($this->aes128_cbc_decrypt($attr, $this->a32_to_str($key)));
   if (substr($b, 0, 6) != 'MEGA{"') return false;
   return json_decode(substr($b, 4),1);
 }
 public function aes128_cbc_decrypt($raw, $key) {
     $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
     $iv = str_repeat("\0",$ivlen);
     return openssl_decrypt($raw,$cipher, $key,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
 }
 public function aes128_cbc_decrypt1($raw, $key,$iv) {
   $ivlen = openssl_cipher_iv_length($cipher="AES-128-CTR");
   //$iv = str_repeat("\0",$ivlen);
   return openssl_decrypt($raw,$cipher, $key,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
 }
 public function getFullLinkMega($param, $key) {
   $m=$this->mega('',json_encode([$param]));
   //print_r($m);
   $enc=$m[0]->at;
   //echo  $enc;
   $enc=$this->base64url_decode($enc);
   $iv = array($key[4], $key[5], 0, 0);
   if (count($key) != 4) {
     $key = array($key[0] ^ $key[4], $key[1] ^ $key[5], $key[2] ^ $key[6], $key[3] ^ $key[7]);
   }
   $attributes = $this->dec_attr($enc, $key);
   $full_direct_link = $m[0]->g .'/' .$attributes['n'].'';
   return $full_direct_link;
 }
 public function mega($path,$paramm)
 {
 $curl = curl_init('https://g.api.mega.co.nz/cs');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($curl, CURLOPT_HEADER, 0);
 if($paramm)
 {
 curl_setopt($curl, CURLOPT_POST, 1);
 curl_setopt($curl, CURLOPT_POSTFIELDS, $paramm);
 }
 $response = curl_exec($curl);
 $code = curl_getinfo($curl);
 curl_close($curl);
 return json_decode($response);
 }
 public function ParseLink($url) {
   $parse_1 = preg_match("/file\/(.+?)#/", $url, $output_file);
   $parse_1 = preg_match("/file\/.*#(.+)/", $url, $output_key);
   $id=$output_file[1];
   $key=$output_key[1];
   $key_plain = $this->base64url_decode($key);
   $key=$this->str_to_a32($key_plain);
   $param=[];
   $param['a']='g';
   $param['g']='1';
   $param['ssl']='2';
   $param['v']='2';
   $param['p']=$id;
   $link = $this->getFullLinkMega($param, $key);
   return $link;
 }
 }
