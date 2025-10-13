<?php
// app/Services/UserHashService.php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserHashService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = config('app.key');
    }

    /**
     * Generate a secure hash for user ID
     */
    public function generateHash(int $userId): string
    {
        $timestamp = now()->timestamp;
        $data = $userId . '|' . $timestamp;
        $hash = hash_hmac('sha256', $data, $this->secret);
        
        // Encode the hash and timestamp together
        return base64_encode($hash . '|' . $timestamp . '|' . $userId);
    }

    /**
     * Decode and verify hash to get user ID
     */
    public function verifyHash(string $hash): ?int
    {
        try {
            $decoded = base64_decode($hash);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return null;
            }

            [$receivedHash, $timestamp, $userId] = $parts;
            
            // Check if hash is expired (24 hours)
            if (now()->timestamp - $timestamp > 86400) {
                return null;
            }
            
            // Verify hash
            $data = $userId . '|' . $timestamp;
            $expectedHash = hash_hmac('sha256', $data, $this->secret);
            
            if (!hash_equals($expectedHash, $receivedHash)) {
                return null;
            }
            
            return (int) $userId;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}