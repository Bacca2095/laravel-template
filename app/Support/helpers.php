<?php

if (! function_exists('user_agent')) {
    function user_agent(?\Illuminate\Http\Request $request = null): ?string
    {
        $request ??= request();

        return $request?->header('User-Agent');
    }
}
