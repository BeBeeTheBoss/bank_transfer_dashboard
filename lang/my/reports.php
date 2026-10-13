<?php

return [
    'export' => [
        'columns' => [
            'transaction' => 'Transaction နံပါတ်',
            'branch' => 'ဆိုင်ခွဲ',
            'requester' => 'တောင်းဆိုသူ',
            'requester_id' => 'တောင်းဆိုသူ ID',
            'requested_at' => 'တောင်းဆိုချိန်',
            'responded_at' => 'တုံ့ပြန်ချိန်',
            'response_seconds' => 'တုံ့ပြန်ကြာချိန် (စက္ကန့်)',
            'amount' => 'ပမာဏ (ကျပ်)',
            'status' => 'အခြေအနေ',
            'responder' => 'တုံ့ပြန်သူ',
            'remark' => 'မှတ်ချက်',
            'requested_screenshot' => 'တောင်းဆိုမှု Screenshot',
            'responded_screenshot' => 'တုံ့ပြန်မှု Screenshot',
        ],
    ],
    'database' => [
        'local_error' => 'Cloud PostgreSQL ကို ချိတ်ဆက်၍မရသေးပါ။ .env ထဲရှိ REPORT_DB_* တန်ဖိုးများနှင့် table အမည်ကို စစ်ဆေးပါ။',
        'error' => 'အစီရင်ခံစာဒေတာကို ယာယီရယူ၍မရပါ။ နောက်တစ်ကြိမ် ပြန်လည်ကြိုးစားပါ။',
    ],
    'photo_import' => [
        'not_configured' => 'Transaction image API ကို မသတ်မှတ်ရသေးပါ။',
        'connection_error' => 'Transaction image API ကို ချိတ်ဆက်၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'failed' => 'ပုံကို စစ်ဆေး၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'success' => 'ပုံကို စစ်ဆေးရန် အောင်မြင်စွာ ပို့ပြီးပါပြီ။',
    ],
    'manual_response' => [
        'not_configured' => 'Manual response API ကို မသတ်မှတ်ရသေးပါ။',
        'connection_error' => 'Manual response API ကို ချိတ်ဆက်၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'failed' => 'တစ်ခုချင်းတုံ့ပြန်မှုကို စစ်ဆေး၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'success' => 'တစ်ခုချင်းတုံ့ပြန်မှုကို အောင်မြင်စွာ ပို့ပြီးပါပြီ။',
    ],
    'manual_failed_response' => [
        'not_configured' => 'Manual failed-response API ကို မသတ်မှတ်ရသေးပါ။',
        'connection_error' => 'Manual failed-response API ကို ချိတ်ဆက်၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'failed' => 'မအောင်မြင်ကြောင်း တုံ့ပြန်မှုကို စစ်ဆေး၍မရပါ။ ထပ်မံကြိုးစားပါ။',
        'success' => 'Transaction ကို မအောင်မြင်ဟု သတ်မှတ်ပြီးပါပြီ။',
    ],
];
