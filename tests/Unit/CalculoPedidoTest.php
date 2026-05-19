<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculoPedidoTest extends TestCase
{
    public function test_calculo_de_igic_y_subtotal_es_matematicamente_correcto(): void
    {
        // Definicion de variables del pedido simulado
        $precioBase = 150.00;
        $tasaImpuesto = 0.07; 

        // Ejecucion del algoritmo de calculo aplicando redondeo financiero
        $igicCalculado = round($precioBase * $tasaImpuesto, 2);
        $totalFinal = round($precioBase + $igicCalculado, 2);

        // Aserciones para validar que el resultado es exacto
        $this->assertEquals(10.50, $igicCalculado);
        $this->assertEquals(160.50, $totalFinal);
    }
}