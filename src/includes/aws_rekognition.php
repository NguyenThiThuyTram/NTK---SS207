<?php
/**
 * AWS Rekognition API Client (Standalone - No Composer needed)
 * Uses AWS Signature V4 Authentication
 * Specially designed for AWS Learner Lab (supports AWS_SESSION_TOKEN)
 * 
 * Credentials Priority:
 *   1. Local file: config/aws_credentials.json (no restart needed!)
 *   2. Environment variables (via Apache SetEnv / Docker)
 */

class AwsRekognition {
    private $accessKey;
    private $secretKey;
    private $sessionToken;
    private $region;
    private $service = 'rekognition';
    private $endpoint;

    /** Path to local credentials file (relative to src/) */
    private static $credentialsFile = __DIR__ . '/../config/aws_credentials.json';

    public function __construct() {
        // ── Priority 1: Read from local JSON file (instant, no restart) ──
        $fileCreds = self::loadFromFile();

        if ($fileCreds) {
            $this->accessKey    = $fileCreds['aws_access_key_id'] ?? '';
            $this->secretKey    = $fileCreds['aws_secret_access_key'] ?? '';
            $this->sessionToken = $fileCreds['aws_session_token'] ?? '';
            $this->region       = $fileCreds['aws_region'] ?? 'us-east-1';
        } else {
            // ── Priority 2: Environment variables (Docker / Apache SetEnv) ──
            $this->accessKey    = getenv('AWS_ACCESS_KEY_ID') ?: '';
            $this->secretKey    = getenv('AWS_SECRET_ACCESS_KEY') ?: '';
            $this->sessionToken = getenv('AWS_SESSION_TOKEN') ?: '';
            $this->region       = getenv('AWS_REGION') ?: 'us-east-1';
        }

        $this->endpoint = "https://rekognition.{$this->region}.amazonaws.com/";
    }

    /**
     * Load credentials from local JSON file
     * @return array|null  Returns credentials array or null if file not found/invalid
     */
    private static function loadFromFile() {
        $file = self::$credentialsFile;
        if (!file_exists($file)) return null;

        $content = @file_get_contents($file);
        if (!$content) return null;

        $data = json_decode($content, true);
        if (!$data || empty($data['aws_access_key_id']) || empty($data['aws_secret_access_key'])) {
            return null;
        }

        return $data;
    }

    /**
     * Save credentials to local JSON file (used by admin UI)
     * @return bool  True on success
     */
    public static function saveCredentials($accessKey, $secretKey, $sessionToken = '', $region = 'us-east-1') {
        $data = [
            'aws_access_key_id'     => trim($accessKey),
            'aws_secret_access_key' => trim($secretKey),
            'aws_session_token'     => trim($sessionToken),
            'aws_region'            => trim($region) ?: 'us-east-1',
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        $dir = dirname(self::$credentialsFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return file_put_contents(self::$credentialsFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Get current credential source info (for admin display)
     */
    public static function getCredentialInfo() {
        $fileCreds = self::loadFromFile();
        if ($fileCreds) {
            return [
                'source'     => 'file',
                'source_label' => '📁 File: config/aws_credentials.json',
                'key_preview'  => substr($fileCreds['aws_access_key_id'], 0, 8) . '...',
                'has_token'    => !empty($fileCreds['aws_session_token']),
                'region'       => $fileCreds['aws_region'] ?? 'us-east-1',
                'updated_at'   => $fileCreds['updated_at'] ?? 'Không rõ',
            ];
        }

        $envKey = getenv('AWS_ACCESS_KEY_ID');
        if ($envKey) {
            return [
                'source'     => 'env',
                'source_label' => '⚙️ Biến môi trường (Apache/Docker)',
                'key_preview'  => substr($envKey, 0, 8) . '...',
                'has_token'    => !empty(getenv('AWS_SESSION_TOKEN')),
                'region'       => getenv('AWS_REGION') ?: 'us-east-1',
                'updated_at'   => 'N/A',
            ];
        }

        return [
            'source'     => 'none',
            'source_label' => '❌ Chưa cấu hình',
            'key_preview'  => '',
            'has_token'    => false,
            'region'       => 'us-east-1',
            'updated_at'   => 'N/A',
        ];
    }

    /**
     * Call DetectLabels API
     */
    public function detectLabels($imageBytes, $maxLabels = 10, $minConfidence = 70.0) {
        if (!$this->isConfigured()) return ['error' => 'AWS Credentials not configured'];

        $payload = json_encode([
            'Image' => [
                'Bytes' => base64_encode($imageBytes)
            ],
            'MaxLabels' => $maxLabels,
            'MinConfidence' => $minConfidence
        ]);

        return $this->sendRequest('RekognitionService.DetectLabels', $payload);
    }

    /**
     * Call DetectModerationLabels API (For Image Moderation)
     */
    public function detectModerationLabels($imageBytes, $minConfidence = 60.0) {
        if (!$this->isConfigured()) return ['error' => 'AWS Credentials not configured'];

        $payload = json_encode([
            'Image' => [
                'Bytes' => base64_encode($imageBytes)
            ],
            'MinConfidence' => $minConfidence
        ]);

        return $this->sendRequest('RekognitionService.DetectModerationLabels', $payload);
    }

    public function isConfigured() {
        return !empty($this->accessKey) && !empty($this->secretKey);
    }

    /**
     * Send HTTP Request with AWS Signature V4
     */
    private function sendRequest($target, $payload) {
        $method = 'POST';
        $uri = '/';
        $query = '';
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        
        $headers = [
            'content-type: application/x-amz-json-1.1',
            'host: rekognition.' . $this->region . '.amazonaws.com',
            'x-amz-date: ' . $amzDate,
            'x-amz-target: ' . $target
        ];
        
        if (!empty($this->sessionToken)) {
            $headers[] = 'x-amz-security-token: ' . $this->sessionToken;
        }

        // Canonical Request (Headers must be sorted alphabetically by header name)
        $canonicalHeaders = "content-type:application/x-amz-json-1.1\n" .
                            "host:rekognition.{$this->region}.amazonaws.com\n" .
                            "x-amz-date:{$amzDate}\n";
        
        if (!empty($this->sessionToken)) {
            $canonicalHeaders .= "x-amz-security-token:{$this->sessionToken}\n";
        }
        
        $canonicalHeaders .= "x-amz-target:{$target}\n";

        if (!empty($this->sessionToken)) {
            $signedHeaders = "content-type;host;x-amz-date;x-amz-security-token;x-amz-target";
        } else {
            $signedHeaders = "content-type;host;x-amz-date;x-amz-target";
        }

        $payloadHash = hash('sha256', $payload);
        $canonicalRequest = "$method\n$uri\n$query\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

        // String to Sign
        $credentialScope = "$dateStamp/{$this->region}/{$this->service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        // Calculate Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Add Authorization header
        $authorizationHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
        $headers[] = 'Authorization: ' . $authorizationHeader;

        // Execute cURL
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return ['error' => 'AWS API Error', 'details' => $result];
        }
        
        return $result;
    }
}
?>
