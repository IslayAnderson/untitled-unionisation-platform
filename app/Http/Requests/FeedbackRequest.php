<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Feedback about the platform. Sent from the popup on any page, so errors are
 * shown on the feedback form's own page.
 */
class FeedbackRequest extends FormRequest
{
    protected $redirectRoute = 'feedback.create';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feedback_name' => ['nullable', 'string', 'max:255'],
            'feedback_email' => ['nullable', 'string', 'email', 'max:255'],
            'feedback_details' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'feedback_name.max' => 'Your name must be 255 characters or fewer',
            'feedback_email.email' => 'Enter an email address in the correct format, like name@example.com',
            'feedback_email.max' => 'Your email address must be 255 characters or fewer',
            'feedback_details.required' => 'Enter your feedback',
            'feedback_details.max' => 'Your feedback must be 5,000 characters or fewer',
        ];
    }
}
