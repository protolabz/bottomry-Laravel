<?php 

echo "asdadada";

 		define('SHOPIFY_APP_SECRET', '0370513481a77b915351ac0db09995b7');

        function verify_webhook($data, $hmac_header)
        {
            $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
            return hash_equals($hmac_header, $calculated_hmac);
        }

        $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
        $data = file_get_contents('php://input');
        $verified = verify_webhook($data, $hmac_header);
        error_log('Webhook verified: '.var_export($verified, true)); //check error.log to see the result*/


echo "yes";

?>