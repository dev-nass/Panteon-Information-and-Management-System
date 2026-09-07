<?php

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

it('can send contact email', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '09171234567',
        'message' => 'Hello, I would like to inquire about burial services and availability.',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Mail::assertQueued(ContactMail::class, function (ContactMail $mail) use ($payload) {
        return $mail->visitorEmail === $payload['email']
            && $mail->firstName === $payload['first_name']
            && $mail->lastName === $payload['last_name']
            && $mail->phoneNumber === $payload['phone_number']
            && $mail->visitorMessage === $payload['message'];
    });
});

it('can send contact email without phone number', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria@example.com',
        'message' => 'This is a valid inquiry message with more than ten characters.',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Mail::assertQueued(ContactMail::class, function (ContactMail $mail) {
        return $mail->phoneNumber === null;
    });
});

it('validates required fields', function () {
    Mail::fake();

    $response = $this->post(route('contact.store'), []);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'message']);
    Mail::assertNothingQueued();
});

it('validates phone number must start with 09 and be 11 digits', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '12345',
        'message' => 'Hello, I would like to inquire about burial services.',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertSessionHasErrors(['phone_number']);
    Mail::assertNothingQueued();
});

it('rejects phone number not starting with 09', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '08171234567',
        'message' => 'Hello, I would like to inquire about burial services.',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertSessionHasErrors(['phone_number']);
    Mail::assertNothingQueued();
});

it('validates email format', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'not-an-email',
        'phone_number' => '09171234567',
        'message' => 'Hello, I would like to inquire about burial services.',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertSessionHasErrors(['email']);
    Mail::assertNothingQueued();
});

it('validates message minimum length', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '09171234567',
        'message' => 'Short',
    ];

    $response = $this->post(route('contact.store'), $payload);

    $response->assertSessionHasErrors(['message']);
    Mail::assertNothingQueued();
});

it('sends queued mail to configured contact address', function () {
    Mail::fake();

    config()->set('mail.contact.address', 'panteondedasmasystem@gmail.com');
    config()->set('mail.from.address', 'panteondedasmasystem@gmail.com');

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '09171234567',
        'message' => 'Hello, I would like to inquire about burial services.',
    ];

    $this->post(route('contact.store'), $payload);

    Mail::assertQueued(ContactMail::class, function (ContactMail $mail) {
        $envelope = $mail->envelope();

        return $envelope->subject === 'New Contact Inquiry from Juan Dela Cruz'
            && $envelope->replyTo[0]->address === 'juan@example.com';
    });
});

it('throttles excessive contact submissions', function () {
    Mail::fake();

    $payload = [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'phone_number' => '09171234567',
        'message' => 'Hello, I would like to inquire about burial services.',
    ];

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('contact.store'), $payload)->assertRedirect();
    }

    $response = $this->post(route('contact.store'), $payload);

    $response->assertSessionHasErrors(['message']);
});
