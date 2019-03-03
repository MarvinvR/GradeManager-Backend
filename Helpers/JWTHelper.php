<?php
    require 'vendor/autoload.php';
    use \Firebase\JWT\JWT;

    class JWTHelper {

        private $secret_key = "";
        private $expiration_time = 604800;
        private $alg = 'HS256';

        public function __construct() {
            require_once '../DatabaseConnection/DatabaseConfig.php';
            
            $this->secret_key = JWT_PRIVATE;
        }

        public function createJWT($user) {
            $issuedAt = time();
            $exp = $issuedAt + $this->expiration_time;
            $payload = array(
                'user' => $user,
                'iat' => $issuedAt,
                'exp' => $exp
            );

            return JWT::encode($payload, $this->secret_key, $this->alg);
        }

        public function verifyJWT($jwt) {
            try {
                $decoded = JWT::decode($jwt, $this->secret_key, array('HS256'));
                return $decoded;
            } catch(Exception $e) {
                return 0;
            }
        }
    }
?>
