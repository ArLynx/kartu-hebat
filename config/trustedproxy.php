<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Daftar IP/subnet reverse proxy (load balancer) yang dipercaya mengisi
    | header X-Forwarded-For / X-Forwarded-Proto. Gunakan nilai paling sempit
    | yang benar-benar menjadi proxy depan aplikasi — jangan seluruh RFC1918,
    | agar header tidak bisa dipalsukan oleh client di subnet yang sama
    | (yang bisa mengakali rate limiter yang berbasis pada request()->ip()).
    |
    | Nilai default kosong = tidak ada proxy yang dipercaya. Ketika aplikasi
    | berada di belakang load balancer, isi dengan alamat LB/nginx, misalnya:
    |
    |   TRUSTED_PROXIES=10.0.0.5,10.0.0.6
    |   TRUSTED_PROXIES=172.31.0.0/20
    |
    */
    'proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),

];
