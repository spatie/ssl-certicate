<?php

namespace Spatie\SslCertificate;

use Spatie\SslCertificate\Exceptions\CouldNotDownloadCertificate;
use Throwable;

class Downloader
{
    public static function downloadCertificateFromUrl(string $url, int $timeout = 30): array
    {
        $hostName = (new Url($url))->getHostName();

        $streamContext = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'capture_peer_cert' => true,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        try {
            $client = stream_socket_client(
                "ssl://{$hostName}:443",
                $errorNumber,
                $errorDescription,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $streamContext);
        } catch (Throwable $thrown) {
            if (str_contains($thrown->getMessage(), 'getaddrinfo failed')) {
                throw CouldNotDownloadCertificate::hostDoesNotExist($hostName);
            }

            throw CouldNotDownloadCertificate::unknownError($hostName, $thrown->getMessage());
        }

        $response = stream_context_get_params($client);

        return openssl_x509_parse($response['options']['ssl']['peer_certificate']);
    }
}
