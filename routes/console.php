<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\ShopSession;

// Auto-close shop at 5 PM every day
Schedule::call(function () {
    if (ShopSession::shouldAutoClose()) {
        $session = ShopSession::today()->open()->first();
        
        if ($session) {
            // Get first admin as the "system" closer
            $adminId = \App\Models\Admin::first()->id;
            
            $session->closeShop($adminId, true); // true = auto close
            
            \Illuminate\Support\Facades\Log::info('Shop automatically closed at 5 PM', [
                'session_id' => $session->id,
                'date' => $session->date,
            ]);
        }
    }
})->dailyAt('17:00')->name('auto-close-shop');
