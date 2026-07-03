<?php

return [
    'ruc'          => env('GREENTER_RUC', '20603371292'),
    'razon_social' => env('GREENTER_RAZON_SOCIAL', 'ASOCIACION DE TRANSPORTISTAS SHINHUA DE PUÑOS'),
    'nombre_comercial' => env('GREENTER_NOMBRE_COMERCIAL', 'SHINHUA TRANSPORTES'),
    'cert_path'    => env('GREENTER_CERT_PATH', 'certs/cert.pfx'),
    'cert_pass'    => env('GREENTER_CERT_PASS', ''),
    'sol_user'     => env('GREENTER_SOL_USER', ''),
    'sol_pass'     => env('GREENTER_SOL_PASS', ''),
    'produccion'   => env('GREENTER_PRODUCCION', false),
    'serie_boleta' => env('GREENTER_SERIE_BOLETA', 'B001'),
    'serie_factura'=> env('GREENTER_SERIE_FACTURA', 'F001'),
    'serie_gre'    => env('GREENTER_SERIE_GRE', 'T001'),

    // Datos de la agencia principal (Puños)
    'ubigeo'       => '100801',  // Puños, Huánuco
    'departamento' => 'HUANUCO',
    'provincia'    => 'HUAMALIES',
    'distrito'     => 'PUÑOS',
    'direccion'    => 'JR. PRINCIPAL S/N, PUÑOS',
    'cod_local'    => '0000',

    // URLs SUNAT
    'url_fe_beta'  => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
    'url_re_beta'  => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService',
    'url_gre_beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService',
    'url_fe_prod'  => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
    'url_re_prod'  => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService',
    'url_gre_prod' => 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService',
];
