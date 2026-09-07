<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $recipient = config('mail.contact.address', config('mail.from.address', 'panteondedasmasystem@gmail.com'));

            Mail::to($recipient)->queue(new ContactMail(
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                visitorEmail: $validated['email'],
                phoneNumber: $validated['phone_number'] ?? null,
                visitorMessage: $validated['message'],
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to queue contact email', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            return back()->withErrors([
                'email' => 'Failed to send your message. Please try again later.',
            ]);
        }

        return back()->with('success', 'Your message has been sent successfully. We\'ll get back to you in 1-2 business days.');
    }
}
