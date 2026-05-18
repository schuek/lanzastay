<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tarifa y fiscalidad (facturación estancia)
    |--------------------------------------------------------------------------
    */

    'precio_noche_default' => (float) env('HOTEL_PRECIO_NOCHE_DEFAULT', 95),

    'igic_percent' => (float) env('HOTEL_IGIC_PERCENT', 7),

];
