<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    public static function member_url($route="") {
        return config('app.url')."/".$route;
    }

    public static function admin_url($route="") {
        return config('app.admin_url')."/".$route;
    }

    public static function query_params($query=[]) {
        return "?".http_build_query($query);
    }

    public static function generateRandomString($length = 30, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $randomString;
    }

    public static function areaList() {
        return [
            "Alam", "Ayer Itam", "Bagan Serai", "Batu Kawan", "Bayan Lepas", "Bedong", "Bertam", "Bukit Mertajam", "Bukit Minyak", "Bukit Tengah", "Butterworth", "Gelugor", "GeorgeTown", "Gurun", "Jawi", "Jelutong", "Juru", "Kota Permai", "Kuala Kurau", "Kuala Muda", "Kulim", "Nibong Tebal", "Padang Serai", "Pantai Remis", "Parit Buntar", "Perai", "Selama", "Serdang", "SG Ara", "Simpang Ampat", "Sungai Petani", "Tambun", "Tanjung Tokong", "Tasek Gelugor"
        ];
    }

public static function sql_sync_status_badge($status = null, $message = null)
    {
        // Define possible status → label/color mappings
        $statusMap = [
            'SUCCESS' => ['label' => 'Success', 'color' => 'success'],
            'ERROR'   => ['label' => 'Failed', 'color' => 'danger'],
            'FAILED'  => ['label' => 'Error', 'color' => 'warning'],
            'DELETE'  => ['label' => 'Deleted', 'color' => 'info'],
        ];

        // If unknown status, fallback to default
        $label = $statusMap[$status]['label'] ?? ucfirst(strtolower($status));
        $color = $statusMap[$status]['color'] ?? 'secondary';

        // Escape tooltip text to avoid HTML/JS injection
        $tooltip = htmlspecialchars($message ?? 'No message available', ENT_QUOTES, 'UTF-8');

        // Return a Bootstrap badge with tooltip
        return sprintf(
            '<span class="badge bg-%s" data-bs-toggle="tooltip" title="%s">%s</span>',
            $color,
            $tooltip,
            e($label)
        );
    }
}