<?php

$Settings_model = new \App\Models\Settings_model();

$defaults = array(
    "whatsapp.enabled" => "",
    "whatsapp.apiUrl" => "http://129.121.46.105:3001/api/messages/send",
    "whatsapp.token" => "",
    "whatsapp.id" => "260687"
);

foreach ($defaults as $key => $value) {
    if (get_setting($key) === null) {
        $Settings_model->save_setting($key, $value);
    }
}
