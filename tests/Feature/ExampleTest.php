<?php
namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_dirige_a_proyectos(): void
    {
        $this->get('/')->assertRedirect('/proyectos');
    }
}
