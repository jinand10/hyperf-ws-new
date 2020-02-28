<?php

if (is_file('./.env')) {
    $env = parse_ini_file('./.env', true);
    $redis_host = $env['REDIS_HOST'];
    $redis_port = $env['REDIS_PORT'];
    $redis_auth = $env['REDIS_AUTH'];
    $redis_db = $env['REDIS_DB'];

    $redis = new \Redis();
    $redis->connect($redis_host, $redis_port);
    if ($redis_auth) {
        $res = $redis->auth($redis_auth);
        if (!$res) {
            exit;
        }
    }
    $ws = $redis->keys('ws:connect:*');
    if ($ws) {
        foreach ($ws as $key) {
            $res = $redis->del($key);
            if ($res) {
                echo "flush success {$key}".PHP_EOL;
            }
        }
    }
}