<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BusinessTestCase;

class PersonalProfileTest extends BusinessTestCase
{
    public function test_personal_details_photo_birthday_and_password(): void
    {
        Storage::fake('local');
        $user = $this->userWithPermissions();
        $this->actingAs($user)->post(route('profile.details'), [
            'nombres' => 'Ana María', 'apellidos' => 'Torres',
            'fecha_nacimiento' => today()->subYears(25)->toDateString(),
            'correo' => 'ana@example.org', 'celular' => '+51 999 123 456',
            'foto' => new UploadedFile(public_path('img/logo.png'), 'perfil.png', 'image/png', null, true),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Ana María Torres', $user->refresh()->nombre_completo);
        $this->get(route('profile.index'))->assertSee('¡Hoy es tu cumpleaños, Ana María!');
        $this->get(route('profile.photo'))->assertOk();
        $fields = ['password' => 'Nueva-clave-123', 'password_confirmation' => 'Nueva-clave-123'];
        $this->post(route('profile.password'), $fields + ['current_password' => 'incorrecta'])->assertSessionHasErrors('current_password');
        $this->post(route('profile.password'), $fields + ['current_password' => 'test-password'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($fields['password'], $user->refresh()->password));
        $this->actingAs($this->userWithPermissions())->get(route('profile.photo'))->assertNotFound();
    }
}
