<?php

if (!function_exists('assistente_ia_can')) {
    function assistente_ia_can(string $permission): bool
    {
        return has_permission($permission);
    }
}
