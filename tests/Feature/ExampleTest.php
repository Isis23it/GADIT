<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_un_invitado_en_la_raiz_va_al_inicio_de_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
