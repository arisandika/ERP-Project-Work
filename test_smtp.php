<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$context = stream_context_create();
$socket = stream_socket_client(
    "ssl://smtp-relay.brevo.com:465",
    $errno,
    $errstr,
    10,
    STREAM_CLIENT_CONNECT,
    $context
);

if (!$socket) {
    echo "GAGAL: $errstr ($errno)\n";
} else {
    echo "SUKSES connect!\n";
    fclose($socket);
}
